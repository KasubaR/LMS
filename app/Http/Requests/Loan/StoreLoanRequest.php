<?php

namespace App\Http\Requests\Loan;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(fn ($query) => $query->where('status', Customer::STATUS_ACTIVE)),
            ],
            'loan_officer_id' => ['nullable', 'integer', 'exists:users,id'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:1000'],
            'interest_type' => ['required', 'string', Rule::in(Loan::interestTypes())],
            'duration_months' => ['required', 'integer', 'min:1', 'max:120'],
            'frequency' => ['required', 'string', Rule::in(Loan::frequencies())],
            'start_date' => ['required', 'date'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'penalty_type' => ['required', 'string', Rule::in(Loan::penaltyTypes())],
            'penalty_value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $officerId = $this->input('loan_officer_id');

            if (! $officerId) {
                return;
            }

            $officer = User::query()->find($officerId);

            if (! $officer || ! $officer->isActive()) {
                $validator->errors()->add('loan_officer_id', 'The selected loan officer is invalid.');
            }
        });
    }
}
