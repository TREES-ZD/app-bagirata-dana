<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'zendesk_view_id' => "360001440115",
            'zendesk_view_title' => "test",
            'zendesk_view_position' => fake()->randomDigitNotNull,
            'interval' => "everyMinute",
            'group_id' => 10,
            'limit' => "unlimited",
            'enabled' => true,
        ];
    }
}
