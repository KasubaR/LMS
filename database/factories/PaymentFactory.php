<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'payment_number' => sprintf('PAY-%s-%04d', now()->format('Y'), $sequence),
            'loan_id' => Loan::factory(),
            'customer_id' => Customer::factory(),
            'recorded_by' => User::factory(),
            'amount' => 100,
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'paid_at' => now(),
            'notes' => null,
            'status' => Payment::STATUS_POSTED,
        ];
    }
}
