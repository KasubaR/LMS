<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class CustomerTimeline
{
    /**
     * Build a paginated customer history feed from Spatie activity_log.
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
    public function forCustomer(Customer $customer, int $perPage = 25): LengthAwarePaginator
    {
        $loanIds = $customer->loans()->pluck('id');
        $paymentIds = $customer->payments()->pluck('id');

        $customerType = $customer->getMorphClass();
        $loanType = (new Loan)->getMorphClass();
        $paymentType = (new Payment)->getMorphClass();

        $paginator = Activity::query()
            ->with(['causer', 'subject'])
            ->inLog(['Customers', 'Loans', 'Payments'])
            ->where(function ($query) use ($customer, $customerType, $loanType, $paymentType, $loanIds, $paymentIds) {
                $query->where(function ($q) use ($customer, $customerType) {
                    $q->where('subject_type', $customerType)
                        ->where('subject_id', $customer->id);
                });

                if ($loanIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($loanType, $loanIds) {
                        $q->where('subject_type', $loanType)
                            ->whereIn('subject_id', $loanIds);
                    });
                }

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

        return $changed->count() === 1 && $changed->first() === 'next_due_date';
    }

    private function labelFor(Activity $activity, mixed $subject): string
    {
        $description = (string) $activity->description;

        if ($description === 'Customer Edited') {
            return $this->refineCustomerEdited($activity);
        }

        if ($subject instanceof Loan) {
            $loanNumber = $subject->loan_number ?: (string) $subject->id;

            return match ($description) {
                'Loan Created' => "Loan #{$loanNumber} Created",
                'Loan Approved' => "Loan #{$loanNumber} Approved",
                'Loan Deleted' => "Loan #{$loanNumber} Deleted",
                'Loan Edited' => "Loan #{$loanNumber} Edited",
                default => $description,
            };
        }

        if ($subject instanceof Payment) {
            $amount = number_format((float) $subject->amount, 2);

            return match ($description) {
                'Payment Recorded' => "Payment K{$amount}",
                'Payment Reversed' => "Payment K{$amount} Reversed",
                'Payment Updated' => "Payment K{$amount} Updated",
                default => $description,
            };
        }

        return $description;
    }

    private function refineCustomerEdited(Activity $activity): string
    {
        $changes = $activity->attribute_changes?->toArray() ?? [];
        $newValues = is_array($changes['attributes'] ?? null) ? $changes['attributes'] : [];
        $oldValues = is_array($changes['old'] ?? null) ? $changes['old'] : [];
        $keys = collect(array_unique([...array_keys($oldValues), ...array_keys($newValues)]));

        if ($keys->count() === 1) {
            $attribute = $keys->first();

            if ($attribute === 'phone') {
                return 'Phone Updated';
            }

            if ($attribute === 'email') {
                return 'Email Updated';
            }

            if ($attribute === 'status') {
                $newStatus = $newValues['status'] ?? null;

                if ($newStatus === Customer::STATUS_ARCHIVED) {
                    return 'Customer Archived';
                }

                if ($newStatus === Customer::STATUS_ACTIVE) {
                    return 'Customer Restored';
                }
            }

            return match ($attribute) {
                'name' => 'Name Updated',
                'nrc' => 'NRC Updated',
                'address' => 'Address Updated',
                'occupation' => 'Occupation Updated',
                'collateral' => 'Collateral Updated',
                'notes' => 'Notes Updated',
                default => 'Customer Edited',
            };
        }

        return 'Customer Edited';
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
        if ($subject instanceof Loan) {
            return [
                (string) ($subject->loan_number ?: $subject->id),
                route('loans.show', $subject),
            ];
        }

        if ($subject instanceof Payment) {
            return [
                (string) ($subject->payment_number ?: $subject->id),
                route('payments.show', $subject),
            ];
        }

        return [null, null];
    }
}
