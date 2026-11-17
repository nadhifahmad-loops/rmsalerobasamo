<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cashier_id' => User::factory(),
            'opening_cash' => 100_000,
            'expected_cash' => null,
            'actual_cash' => null,
            'difference' => null,
            'status' => Shift::STATUS_OPEN,
            'opened_at' => now()->subHours(2),
            'closed_at' => null,
        ];
    }

    /**
     * Indicate that the shift has been closed and reconciled.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Shift::STATUS_CLOSED,
            'expected_cash' => 150_000,
            'actual_cash' => 148_000,
            'difference' => -2_000,
            'closed_at' => now(),
        ]);
    }
}
