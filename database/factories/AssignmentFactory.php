<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            "type" => Agent::ASSIGNMENT,
            "zendesk_view_id" => fake()->randomNumber,
            "batch_id" => "some-uuid-some-uuid",
            "agent_id" => fake()->randomDigit,
            "agent_name" => sprintf("%s (%s, %s)", fake()->name, fake()->company, fake()->name),
            "zendesk_ticket_id" => fake()->unique()->randomDigit,
            "zendesk_ticket_subject" => fake()->sentence(),
            "response_status" => "200"
        ];
    }

    public function unassignment()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Agent::UNASSIGNMENT,
            ];
        });
    }

    public function already_solved()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => "ALREADY_SOLVED",
            ];
        });
    }
}
