<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'type',
    'original_name',
    'path',
    'mime_type',
    'size',
    'uploaded_by',
])]
class CustomerDocument extends Model
{
    public const TYPE_NRC = 'nrc';

    public const TYPE_PASSPORT = 'passport';

    public const TYPE_PAYSLIP = 'payslip';

    public const TYPE_AGREEMENT = 'agreement';

    public const TYPE_OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_NRC,
            self::TYPE_PASSPORT,
            self::TYPE_PAYSLIP,
            self::TYPE_AGREEMENT,
            self::TYPE_OTHER,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_NRC => 'NRC',
            self::TYPE_PASSPORT => 'Passport',
            self::TYPE_PAYSLIP => 'Payslip',
            self::TYPE_AGREEMENT => 'Agreement',
            default => 'Other',
        };
    }
}
