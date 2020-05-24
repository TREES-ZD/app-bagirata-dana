<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Agent;
use Faker\Generator as Faker;

$factory->define(Agent::class, function (Faker $faker) {
    $name = $faker->name;
    $agentName = $faker->firstName;
    return [
        "priority" => 1,
        "reassign" => false,
        "status" => true,
        "zendesk_agent_id" => $faker->randomDigit,
        "zendesk_agent_name" => $name,
        "zendesk_group_id" => $faker->randomDigit,
        "zendesk_group_name" => $faker->company,
        "zendesk_custom_field_id" => strtolower($agentName),
        "zendesk_custom_field_name" => $agentName,
        "limit" => "unlimited"
    ];
});
