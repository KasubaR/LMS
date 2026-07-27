<?php

namespace App\Http\Requests\Admin;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'loan_number_prefix' => ['required', 'string', 'max:10', 'alpha_dash'],
            'default_interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_penalty_type' => ['required', 'string', 'in:'.implode(',', Loan::penaltyTypes())],
            'default_penalty_value' => ['required', 'numeric', 'min:0'],
            'grace_period_days' => ['required', 'integer', 'min:0', 'max:365'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'currency_symbol' => ['required', 'string', 'max:5'],
        ];
    }
}
