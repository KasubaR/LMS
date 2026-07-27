<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow authenticated admin users to trigger payouts.
        // Adjust this to match your actual auth/role setup.
        return $this->user() && $this->user()->can('send-payouts');
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'regex:/^0(9[567]|7[567])[0-9]{7}$/'],
            'operator' => ['required', 'in:mtn,airtel,zamtel'],
            'amount' => ['required', 'numeric', 'min:1'],
            'narration' => ['nullable', 'string', 'max:255'],
        ];
    }
}
