<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class LoanTimeline
{
    /**
     * Build a paginated loan history feed from Spatie activity_log.
     *
     * @return LengthAwarePaginator<int, array{
     *     id: int,
     *     label: string,
     *     causer_name: string,
     *     record_label: string|null,
     *     record_url: string|null,
     *     created_at: Carbon|null
     * }>
     */
    public function forLoan(Loan $loan, int $perPage = 25): LengthAwarePaginator
    {
        $paymentIds = $loan->payments()->pluck('id');

        $loanType = $loan->getMorphClass();
        $paymentType = (new Payment)->getMorphClass();

        $paginator = Activity::query()
            ->with(['causer', 'subject'])
            ->inLog(['Loans', 'Payments'])
            ->where(function ($query) use ($loan, $loanType, $paymentType, $paymentIds) {
                $query->where(function ($q) use ($loan, $loanType) {
                    $q->where('subject_type', $loanType)
                        ->where('subject_id', $loan->id);
                });

                if ($paymentIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($paymentType, $paymentIds) {
                        $q->where('subject_type', $paymentType)
                            ->whereIn('subject_id', $paymentIds);
                    });
                }
            })
            ->latest()
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()
                ->map(fn (Activity $activity) => $this->mapItem($activity))
                ->filter()
                ->values()
        );

        return $paginator;
    }

    /**
     * @return array{
     *     id: int,
     *     label: string,
     *     causer_name: string,
     *     record_label: string|null,
     *     record_url: string|null,
     *     created_at: Carbon|null
     * }|null
     */
    private function mapItem(Activity $activity): ?array
    {
        if ($this->shouldHide($activity)) {
            return null;
        }

        $subject = $activity->subject;
        $label = $this->labelFor($activity, $subject);

        [$recordLabel, $recordUrl] = $this->recordLink($subject);

        return [
            'id' => $activity->id,
            'label' => $label,
            'causer_name' => $activity->causer?->name ?? 'System',
            'record_label' => $recordLabel,
            'record_url' => $recordUrl,
            'created_at' => $activity->created_at,
        ];
    }

    private function shouldHide(Activity $activity): bool
    {
        if ($activity->description !== 'Loan Edited') {
            return false;
        }

        $changed = $this->changedAttributes($activity);

        // Hide next_due_date-only noise and status-only edits (covered by dedicated labels).
        if ($changed->count() === 1) {
            $only = $changed->first();

            return in_array($only, ['next_due_date', 'status'], true);
        }

        return $changed->every(fn (string $attr) => in_array($attr, ['next_due_date', 'status'], true));
    }

    private function labelFor(Activity $activity, mixed $subject): string
    {
        $description = (string) $activity->description;

        if ($subject instanceof Payment) {
            $amount = number_format((float) $subject->amount, 2);

            return match ($description) {
                'Payment Recorded' => "Payment K{$amount} Received",
                'Payment Reversed' => "Payment K{$amount} Reversed",
                'Payment Updated' => "Payment K{$amount} Updated",
                default => $description,
            };
        }

        return match ($description) {
            'Loan Created' => 'Loan Created',
            'Loan Approved' => 'Approved',
            'Money Disbursed' => 'Money Disbursed',
            'Loan Closed' => 'Loan Closed',
            'Loan Defaulted' => 'Loan Defaulted',
            'Loan Written Off' => 'Loan Written Off',
            'Payment Missed' => $this->paymentMissedLabel($activity),
            'Penalty Applied' => $this->penaltyAppliedLabel($activity),
            'Loan Deleted' => 'Loan Deleted',
            'Loan Edited' => 'Loan Edited',
            default => $description,
        };
    }

    private function paymentMissedLabel(Activity $activity): string
    {
        $dueDate = $activity->getProperty('due_date');

        if (is_string($dueDate) && $dueDate !== '') {
            return "Payment Missed ({$dueDate})";
        }

        return 'Payment Missed';
    }

    private function penaltyAppliedLabel(Activity $activity): string
    {
        $amount = $activity->getProperty('penalty_amount');

        if (is_numeric($amount)) {
            return 'Penalty Applied K'.number_format((float) $amount, 2);
        }

        return 'Penalty Applied';
    }

    /**
     * @return Collection<int, string>
     */
    private function changedAttributes(Activity $activity): Collection
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $newValues = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $oldValues = is_array($changes['old'] ?? null) ? $changes['old'] : [];

        return collect(array_unique([...array_keys($oldValues), ...array_keys($newValues)]));
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function recordLink(mixed $subject): array
    {
        if ($subject instanceof Payment) {
            return [
                (string) ($subject->payment_number ?: $subject->id),
                route('payments.show', $subject),
            ];
        }

        return [null, null];
    }
}
