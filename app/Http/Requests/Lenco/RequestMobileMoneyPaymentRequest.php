<?php

namespace App\Http\Requests\Lenco;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LencoCollectionRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RequestMobileMoneyPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')->where(
                    fn ($query) => $query->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ),
            ],
            'loan_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('loan_installments', 'id'),
            ],
            'phone' => ['required', 'string', 'regex:/^0(9[567]|7[567])[0-9]{7}$/'],
            'operator' => ['required', 'string', Rule::in(LencoCollectionRequest::operators())],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $installmentId = $this->integer('loan_installment_id') ?: null;
            $loanId = $this->integer('loan_id') ?: null;

            if (! $installmentId || ! $loanId) {
                return;
            }

            $installment = LoanInstallment::query()->find($installmentId);

            if (! $installment || $installment->loan_id !== $loanId) {
                $validator->errors()->add('loan_installment_id', 'The selected installment does not belong to this loan.');

                return;
            }

            if ($installment->isPaid()) {
                $validator->errors()->add('loan_installment_id', 'The selected installment is already paid.');
            }
        });
    }
}
