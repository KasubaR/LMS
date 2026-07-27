<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nrc' => ['required', 'string', 'max:50', 'unique:customers,nrc', 'regex:/^\d+\/\d+\/\d+$/'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:customers,email'],
            'address' => ['nullable', 'string', 'max:2000'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'collateral' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nrc.regex' => 'The NRC must look like 123456/78/1.',
        ];
    }
}
