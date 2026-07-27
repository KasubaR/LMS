<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'loan_id',
    'sequence',
    'due_date',
    'principal_amount',
    'interest_amount',
    'fee_amount',
    'penalty_amount',
    'amount_due',
    'amount_paid',
    'status',
])]
class LoanInstallment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_WAIVED = 'waived';

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'penalty_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'sequence' => 'integer',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID
            || ((float) $this->amount_paid >= (float) $this->amount_due && (float) $this->amount_due > 0);
    }

    public function isPartial(): bool
    {
        return ! $this->isPaid() && (float) $this->amount_paid > 0;
    }

    /**
     * Display status for schedule UI: paid | partial | overdue | pending | waived.
     */
    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_WAIVED) {
            return 'waived';
        }

        if ($this->isPaid()) {
            return 'paid';
        }

        if ($this->isPartial()) {
            return 'partial';
        }

        if ($this->status === self::STATUS_OVERDUE) {
            return 'overdue';
        }

        return 'pending';
    }

    public function baseAmount(): float
    {
        return (float) $this->principal_amount + (float) $this->interest_amount + (float) $this->fee_amount;
    }

    public function outstanding(): float
    {
        $due = (float) $this->amount_due;
        $paid = (float) $this->amount_paid;

        return max(0, round($due - $paid, 2));
    }

    public function recalculateAmountDue(): void
    {
        $this->amount_due = round($this->baseAmount() + (float) $this->penalty_amount, 2);
    }
}
