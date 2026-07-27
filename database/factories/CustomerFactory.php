<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        return [
            'customer_number' => sprintf('CUS-%s-%04d', now()->format('Y'), $sequence),
            'name' => fake()->name(),
            'nrc' => sprintf('%06d/%02d/%d', fake()->unique()->numberBetween(100000, 999999), fake()->numberBetween(10, 99), fake()->numberBetween(1, 9)),
            'phone' => fake()->numerify('09########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->address(),
            'occupation' => fake()->jobTitle(),
            'collateral' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'status' => Customer::STATUS_ACTIVE,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => Customer::STATUS_ARCHIVED]);
    }
}
