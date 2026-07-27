<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'business_name',
    'loan_number_prefix',
    'default_interest_rate',
    'default_penalty_type',
    'default_penalty_value',
    'grace_period_days',
    'currency_code',
    'currency_symbol',
])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'default_interest_rate' => 'decimal:2',
            'default_penalty_value' => 'decimal:2',
            'grace_period_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], [
            'business_name' => config('app.name'),
            'loan_number_prefix' => config('lms.loan_number_prefix', 'LN'),
            'default_interest_rate' => config('lms.default_interest_rate', 40),
            'default_penalty_type' => config('lms.default_penalty_type', 'fixed'),
            'default_penalty_value' => config('lms.default_penalty_value', 0),
            'grace_period_days' => config('lms.grace_period_days', 0),
            'currency_code' => config('lms.currency_code', 'ZMW'),
            'currency_symbol' => config('lms.currency_symbol', 'K'),
        ]);
    }
}
