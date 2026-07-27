<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        $start = now()->startOfDay();

        return [
            'loan_number' => sprintf('LN-%s-%04d', now()->format('Y'), $sequence),
            'customer_id' => Customer::factory(),
            'loan_officer_id' => null,
            'principal' => 1000,
            'interest_rate' => 40,
            'interest_type' => Loan::INTEREST_FLAT,
            'duration_months' => 1,
            'frequency' => Loan::FREQUENCY_MONTHLY,
            'start_date' => $start,
            'due_date' => $start->copy()->addMonthNoOverflow(),
            'processing_fee' => 0,
            'penalty_type' => Loan::PENALTY_DAILY_PERCENT,
            'penalty_value' => 0,
            'status' => Loan::STATUS_ACTIVE,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => Loan::STATUS_ACTIVE]);
    }
}
