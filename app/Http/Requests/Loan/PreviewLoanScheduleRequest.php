<?php

namespace App\Http\Requests\Loan;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewLoanScheduleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'principal' => ['required', 'numeric', 'min:0.01'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:1000'],
            'interest_type' => ['required', 'string', Rule::in(Loan::interestTypes())],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'frequency' => ['required', 'string', Rule::in(Loan::frequencies())],
            'start_date' => ['required', 'date'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
