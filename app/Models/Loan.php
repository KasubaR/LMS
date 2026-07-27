<?php

namespace App\Models;

use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'loan_number',
    'customer_id',
    'loan_officer_id',
    'principal',
    'interest_rate',
    'interest_type',
    'duration_months',
    'frequency',
    'start_date',
    'due_date',
    'processing_fee',
    'penalty_type',
    'penalty_value',
    'status',
    'notes',
    'approved_at',
    'approved_by',
    'next_due_date',
])]
class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory, LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DEFAULTED = 'defaulted';

    public const STATUS_WRITTEN_OFF = 'written_off';

    public const INTEREST_FLAT = 'flat';

    public const INTEREST_REDUCING = 'reducing';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_BIWEEKLY = 'biweekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const PENALTY_FIXED = 'fixed';

    public const PENALTY_PERCENT_OF_OVERDUE = 'percent_of_overdue';

    public const PENALTY_DAILY_PERCENT = 'daily_percent';

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_OVERDUE,
            self::STATUS_COMPLETED,
            self::STATUS_DEFAULTED,
            self::STATUS_WRITTEN_OFF,
        ];
    }

    /**
     * @return list<string>
     */
    public static function interestTypes(): array
    {
        return [self::INTEREST_FLAT, self::INTEREST_REDUCING];
    }

    /**
     * @return list<string>
     */
    public static function frequencies(): array
    {
        return [self::FREQUENCY_WEEKLY, self::FREQUENCY_BIWEEKLY, self::FREQUENCY_MONTHLY];
    }

    /**
     * @return list<string>
     */
    public static function penaltyTypes(): array
    {
        return [
            self::PENALTY_FIXED,
            self::PENALTY_PERCENT_OF_OVERDUE,
            self::PENALTY_DAILY_PERCENT,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('Loans');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return match ($eventName) {
            'created' => 'Loan Created',
            'updated' => 'Loan Edited',
            'deleted' => 'Loan Deleted',
            'restored' => 'Loan Restored',
            default => ucfirst($eventName),
        };
    }

    protected function casts(): array
    {
        return [
            'principal' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'duration_months' => 'integer',
            'start_date' => 'date',
            'due_date' => 'date',
            'next_due_date' => 'date',
            'processing_fee' => 'decimal:2',
            'penalty_value' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loanOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loan_officer_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class)->orderBy('sequence');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function acceptsPayments(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_OVERDUE], true);
    }

    public function isEditable(): bool
    {
        return ! $this->hasPayments();
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function totalInterest(): string
    {
        return number_format((float) $this->installments->sum('interest_amount'), 2, '.', '');
    }

    public function totalRepayment(): string
    {
        return number_format(
            (float) $this->installments->sum(fn (LoanInstallment $row) => (float) $row->principal_amount + (float) $row->interest_amount + (float) $row->fee_amount),
            2,
            '.',
            ''
        );
    }

    public function balance(): string
    {
        return number_format(
            (float) $this->installments
                ->filter(fn (LoanInstallment $row) => ! $row->isPaid())
                ->sum(fn (LoanInstallment $row) => $row->outstanding()),
            2,
            '.',
            ''
        );
    }

    public function remainingPayments(): int
    {
        return $this->installments->filter(fn (LoanInstallment $row) => ! $row->isPaid())->count();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($term) {
            $builder
                ->where('loan_number', 'like', "%{$term}%")
                ->orWhereHas('customer', function (Builder $customerQuery) use ($term) {
                    $customerQuery
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('nrc', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('customer_number', 'like', "%{$term}%");
                });
        });
    }
}
