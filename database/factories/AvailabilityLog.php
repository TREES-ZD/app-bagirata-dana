<?php

namespace Database\Factories;

use App\Models\AvailabilityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityLog>
 */
class AvailabilityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'id' => 1,
            "agent_id" => fake()->randomDigit,
            "agent_name" => fake()->firstName,
            "causer_id" => null,
            "causer_type" => null,
            "created_at" => now()
        ];
    }
}
