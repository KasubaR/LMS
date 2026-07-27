<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanInstallment;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class LoanPenaltyCalculator
{
    public function forInstallment(
        Loan $loan,
        LoanInstallment $installment,
        ?CarbonInterface $asOf = null
    ): float {
        if ($installment->isPaid() || $installment->status === LoanInstallment::STATUS_WAIVED) {
            return 0.0;
        }

        $asOf ??= Carbon::today();
        $graceEnd = $this->graceEnd($installment);

        if ($asOf->lessThanOrEqualTo($graceEnd)) {
            return 0.0;
        }

        $outstandingBase = max(0, round($installment->baseAmount() - (float) $installment->amount_paid, 2));

        if ($outstandingBase <= 0 && $loan->penalty_type !== Loan::PENALTY_FIXED) {
            return 0.0;
        }

        return match ($loan->penalty_type) {
            Loan::PENALTY_FIXED => round((float) $loan->penalty_value, 2),
            Loan::PENALTY_PERCENT_OF_OVERDUE => round($outstandingBase * ((float) $loan->penalty_value / 100), 2),
            Loan::PENALTY_DAILY_PERCENT => round(
                $outstandingBase * ((float) $loan->penalty_value / 100) * $graceEnd->diffInDays($asOf),
                2
            ),
            default => 0.0,
        };
    }

    /**
     * The last day an installment is still within its grace period — penalties
     * and the "overdue" status only apply once this date has passed.
     */
    private function graceEnd(LoanInstallment $installment): CarbonInterface
    {
        $graceDays = max(0, (int) config('lms.grace_period_days', 0));

        return Carbon::parse($installment->due_date)->startOfDay()->addDays($graceDays);
    }

    public function refreshLoan(Loan $loan, ?CarbonInterface $asOf = null): void
    {
        $asOf ??= Carbon::today();
        $loan->loadMissing('installments');

        $hasOverdue = false;

        foreach ($loan->installments as $installment) {
            if ($installment->isPaid()) {
                continue;
            }

            $previousStatus = $installment->status;
            $previousPenalty = round((float) $installment->penalty_amount, 2);

            $penalty = $this->forInstallment($loan, $installment, $asOf);
            $installment->penalty_amount = $penalty;
            $installment->recalculateAmountDue();

            $isPastDue = $asOf->greaterThan($this->graceEnd($installment));

            if ($isPastDue) {
                $installment->status = LoanInstallment::STATUS_OVERDUE;
                $hasOverdue = true;
            } elseif ($installment->status === LoanInstallment::STATUS_OVERDUE) {
                $installment->status = LoanInstallment::STATUS_PENDING;
            }

            $installment->save();

            if (
                $previousStatus !== LoanInstallment::STATUS_OVERDUE
                && $installment->status === LoanInstallment::STATUS_OVERDUE
            ) {
                AuditLogger::log('Payment Missed', $loan, [
                    'installment_id' => $installment->id,
                    'due_date' => $installment->due_date?->toDateString(),
                    'sequence' => $installment->sequence,
                ], 'Loans');
            }

            $newPenalty = round((float) $installment->penalty_amount, 2);
            if ($newPenalty > $previousPenalty + 0.001) {
                AuditLogger::log('Penalty Applied', $loan, [
                    'installment_id' => $installment->id,
                    'penalty_amount' => $newPenalty,
                    'delta' => round($newPenalty - $previousPenalty, 2),
                    'sequence' => $installment->sequence,
                ], 'Loans');
            }
        }

        if (in_array($loan->status, [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE], true)) {
            $loan->status = $hasOverdue ? Loan::STATUS_OVERDUE : Loan::STATUS_ACTIVE;

            $next = $loan->installments
                ->filter(fn (LoanInstallment $row) => ! $row->isPaid())
                ->sortBy(fn (LoanInstallment $row) => sprintf(
                    '%s-%04d',
                    $row->due_date?->format('Y-m-d') ?? '9999-99-99',
                    $row->sequence
                ))
                ->first();

            $loan->next_due_date = $next?->due_date;
            $loan->save();
        }
    }
}
