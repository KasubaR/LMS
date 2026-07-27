<?php

namespace App\Http\Requests\Payment;

use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', Rule::in(Payment::methods())],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
