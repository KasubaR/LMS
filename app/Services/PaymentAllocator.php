<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentAllocator
{
    public function __construct(
        private LoanPenaltyCalculator $penalties,
        private PaymentNumberGenerator $numbers,
    ) {}

    /**
     * @param  array{
     *     loan_id: int,
     *     amount: float|int|string,
     *     method: string,
     *     reference?: string|null,
     *     paid_at: string|\DateTimeInterface,
     *     notes?: string|null,
     *     recorded_by?: int|null,
     *     loan_installment_id?: int|null
     * }  $data
     */
    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $loan = Loan::query()->lockForUpdate()->with('installments')->findOrFail($data['loan_id']);

            $this->assertPayable($loan);

            $this->penalties->refreshLoan($loan);
            $loan->refresh()->load('installments');

            $amount = round((float) $data['amount'], 2);
            $targetId = isset($data['loan_installment_id']) ? (int) $data['loan_installment_id'] : null;

            if ($targetId) {
                $this->assertTargetInstallment($loan, $targetId, $amount);
            }

            $this->assertAmountWithinBalance($loan, $amount);

            $payment = Payment::create([
                'payment_number' => $this->numbers->generate(),
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'recorded_by' => $data['recorded_by'] ?? null,
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => Carbon::parse($data['paid_at']),
                'notes' => $data['notes'] ?? null,
                'status' => Payment::STATUS_POSTED,
            ]);

            $this->allocate($payment, $loan, $amount, $targetId);
            $this->syncLoanState($loan);

            return $payment->load(['allocations.installment', 'loan', 'customer']);
        });
    }

    /**
     * @param  array{
     *     amount: float|int|string,
     *     method: string,
     *     reference?: string|null,
     *     paid_at: string|\DateTimeInterface,
     *     notes?: string|null
     * }  $data
     */
    public function update(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::query()->lockForUpdate()->with(['allocations', 'loan.installments'])->findOrFail($payment->id);

            if (! $payment->isPosted()) {
                throw new InvalidArgumentException('Only posted payments can be edited.');
            }

            $loan = Loan::query()->lockForUpdate()->with('installments')->findOrFail($payment->loan_id);

            $this->unallocate($payment, $loan);
            $loan->refresh()->load('installments');

            // Completed loans reopened by unallocate may need active/overdue before re-pay
            if ($loan->status === Loan::STATUS_COMPLETED) {
                $loan->status = Loan::STATUS_ACTIVE;
                $loan->save();
            }

            $this->assertPayable($loan);
            $this->penalties->refreshLoan($loan);
            $loan->refresh()->load('installments');

            $amount = round((float) $data['amount'], 2);
            $this->assertAmountWithinBalance($loan, $amount);

            $payment->update([
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'paid_at' => Carbon::parse($data['paid_at']),
                'notes' => $data['notes'] ?? null,
            ]);

            $payment->allocations()->delete();
            $this->allocate($payment, $loan, $amount);
            $this->syncLoanState($loan);

            return $payment->fresh(['allocations.installment', 'loan', 'customer']);
        });
    }

    public function reverse(Payment $payment, User $actor, ?string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $reason) {
            $payment = Payment::query()->lockForUpdate()->with(['allocations', 'loan.installments'])->findOrFail($payment->id);

            if (! $payment->isPosted()) {
                throw new InvalidArgumentException('Payment is already reversed.');
            }

            $loan = Loan::query()->lockForUpdate()->with('installments')->findOrFail($payment->loan_id);

            $this->unallocate($payment, $loan);

            $payment->update([
                'status' => Payment::STATUS_REVERSED,
                'reversed_at' => now(),
                'reversed_by' => $actor->id,
                'reversal_reason' => $reason,
            ]);

            $loan->refresh()->load('installments');

            if ($loan->status === Loan::STATUS_COMPLETED) {
                $loan->status = Loan::STATUS_ACTIVE;
                $loan->save();
            }

            if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
                $this->penalties->refreshLoan($loan);
            }

            $this->syncLoanState($loan->fresh('installments'));

            return $payment->fresh(['allocations.installment', 'loan', 'customer']);
        });
    }

    public function markInstallmentPaid(Loan $loan, LoanInstallment $installment, User $actor): Payment
    {
        if ($installment->loan_id !== $loan->id) {
            throw new InvalidArgumentException('Installment does not belong to this loan.');
        }

        $this->penalties->refreshLoan($loan->load('installments'));
        $loan->refresh()->load('installments');
        $installment = $loan->installments->firstWhere('id', $installment->id) ?? $installment->fresh();

        if ($installment->isPaid()) {
            throw new InvalidArgumentException('Installment is already paid.');
        }

        $outstanding = $installment->outstanding();

        if ($outstanding <= 0) {
            throw new InvalidArgumentException('Installment has no outstanding balance.');
        }

        return $this->record([
            'loan_id' => $loan->id,
            'loan_installment_id' => $installment->id,
            'amount' => $outstanding,
            'method' => Payment::METHOD_CASH,
            'paid_at' => now(),
            'notes' => 'Marked paid from schedule',
            'recorded_by' => $actor->id,
        ]);
    }

    private function assertTargetInstallment(Loan $loan, int $installmentId, float $amount): void
    {
        $installment = $loan->installments->firstWhere('id', $installmentId);

        if (! $installment) {
            throw new InvalidArgumentException('The selected installment was not found on this loan.');
        }

        if ($installment->isPaid() || $installment->status === LoanInstallment::STATUS_WAIVED) {
            throw new InvalidArgumentException('The selected installment cannot accept payments.');
        }

        $outstanding = $installment->outstanding();

        if ($amount > $outstanding + 0.001) {
            throw new InvalidArgumentException(
                'Payment amount exceeds this installment\'s outstanding balance of K'.number_format($outstanding, 2).'.'
            );
        }
    }

    private function assertPayable(Loan $loan): void
    {
        if (! in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
            throw new InvalidArgumentException('Payments can only be recorded against active or overdue loans.');
        }
    }

    private function assertAmountWithinBalance(Loan $loan, float $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $balance = (float) $loan->balance();

        if ($amount > $balance + 0.001) {
            throw new InvalidArgumentException('Payment amount exceeds the remaining loan balance of K'.number_format($balance, 2).'.');
        }
    }

    private function allocate(Payment $payment, Loan $loan, float $amount, ?int $targetInstallmentId = null): void
    {
        $remaining = $amount;
        $installments = $this->fifoInstallments($loan);

        if ($targetInstallmentId) {
            $target = $installments->firstWhere('id', $targetInstallmentId);

            if ($target) {
                $installments = $installments
                    ->reject(fn (LoanInstallment $row) => $row->id === $targetInstallmentId)
                    ->prepend($target)
                    ->values();
            }
        }

        foreach ($installments as $installment) {
            if ($remaining <= 0) {
                break;
            }

            $outstanding = $installment->outstanding();
            if ($outstanding <= 0) {
                continue;
            }

            $apply = round(min($remaining, $outstanding), 2);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'loan_installment_id' => $installment->id,
                'amount' => $apply,
            ]);

            $installment->amount_paid = round((float) $installment->amount_paid + $apply, 2);

            if ($installment->outstanding() <= 0.001) {
                $installment->status = LoanInstallment::STATUS_PAID;
                $installment->amount_paid = (float) $installment->amount_due;
            }

            $installment->save();
            $remaining = round($remaining - $apply, 2);

            // Targeted partial/full payments stay on the chosen installment only.
            if ($targetInstallmentId) {
                break;
            }
        }

        if ($remaining > 0.001) {
            throw new InvalidArgumentException('Unable to fully allocate payment amount.');
        }
    }

    private function unallocate(Payment $payment, Loan $loan): void
    {
        foreach ($payment->allocations as $allocation) {
            $installment = $loan->installments->firstWhere('id', $allocation->loan_installment_id)
                ?? LoanInstallment::query()->lockForUpdate()->find($allocation->loan_installment_id);

            if (! $installment) {
                continue;
            }

            $installment->amount_paid = max(0, round((float) $installment->amount_paid - (float) $allocation->amount, 2));

            if ((float) $installment->amount_paid <= 0) {
                $installment->amount_paid = 0;
                $installment->status = LoanInstallment::STATUS_PENDING;
            } elseif ((float) $installment->amount_paid < (float) $installment->amount_due) {
                $installment->status = LoanInstallment::STATUS_PENDING;
            }

            $installment->save();
        }
    }

    /**
     * @return Collection<int, LoanInstallment>
     */
    private function fifoInstallments(Loan $loan)
    {
        return $loan->installments
            ->filter(fn (LoanInstallment $row) => ! $row->isPaid() && $row->status !== LoanInstallment::STATUS_WAIVED)
            ->sortBy(function (LoanInstallment $row) {
                $overdueRank = $row->status === LoanInstallment::STATUS_OVERDUE ? 0 : 1;
                $due = $row->due_date?->format('Y-m-d') ?? '9999-99-99';

                return sprintf('%d-%s-%04d', $overdueRank, $due, $row->sequence);
            })
            ->values();
    }

    public function syncLoanState(Loan $loan): void
    {
        $loan->load('installments');

        $unpaid = $loan->installments->filter(fn (LoanInstallment $row) => ! $row->isPaid());

        if ($unpaid->isEmpty()) {
            $loan->status = Loan::STATUS_COMPLETED;
            $loan->next_due_date = null;
            $loan->save();

            return;
        }

        $next = $unpaid->sortBy(fn (LoanInstallment $row) => sprintf(
            '%s-%04d',
            $row->due_date?->format('Y-m-d') ?? '9999-99-99',
            $row->sequence
        ))->first();

        $loan->next_due_date = $next?->due_date;

        if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE, Loan::STATUS_COMPLETED], true)) {
            $hasOverdue = $unpaid->contains(
                fn (LoanInstallment $row) => $row->status === LoanInstallment::STATUS_OVERDUE
                    || Carbon::today()->greaterThan(Carbon::parse($row->due_date)->startOfDay())
            );
            $loan->status = $hasOverdue ? Loan::STATUS_OVERDUE : Loan::STATUS_ACTIVE;
        }

        $loan->save();
    }
}
