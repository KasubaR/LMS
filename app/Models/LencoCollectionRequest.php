<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reference',
    'lenco_reference',
    'loan_id',
    'customer_id',
    'loan_installment_id',
    'amount',
    'phone',
    'operator',
    'status',
    'reason_for_failure',
    'raw_response',
    'requested_by',
    'payment_id',
])]
class LencoCollectionRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_OTP_REQUIRED = 'otp-required';

    public const STATUS_PAY_OFFLINE = 'pay-offline';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    public const OPERATOR_MTN = 'mtn';

    public const OPERATOR_AIRTEL = 'airtel';

    /**
     * @return list<string>
     */
    public static function operators(): array
    {
        return [self::OPERATOR_MTN, self::OPERATOR_AIRTEL];
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class, 'loan_installment_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESSFUL, self::STATUS_FAILED], true);
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESSFUL;
    }

    public function operatorLabel(): string
    {
        return match ($this->operator) {
            self::OPERATOR_MTN => 'MTN',
            self::OPERATOR_AIRTEL => 'Airtel',
            default => ucfirst($this->operator),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_OTP_REQUIRED => 'OTP Required',
            self::STATUS_PAY_OFFLINE => 'Awaiting Customer Approval',
            self::STATUS_SUCCESSFUL => 'Successful',
            self::STATUS_FAILED => 'Failed',
            default => ucfirst($this->status),
        };
    }
}
