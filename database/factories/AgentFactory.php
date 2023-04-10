<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $agentName = fake()->firstName;
        return [
            "priority" => 1,
            "reassign" => false,
            "status" => true,
            "zendesk_agent_id" => fake()->randomDigit,
            "zendesk_agent_name" => fake()->name,
            "zendesk_group_id" => fake()->randomDigit,
            "zendesk_group_name" => fake()->company,
            "zendesk_custom_field_id" => strtolower($agentName),
            "zendesk_custom_field_name" => $agentName,
            "limit" => "unlimited"
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function ($agent) {
            $uuid = fake()->uuid;
            AssignmentFactory::new()->count(5)->create([
                'agent_id' => $agent->id,
                "batch_id" => $uuid
            ]);
        });
    }

    public function unavailable()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => false,
            ];
        });  
    }
}
