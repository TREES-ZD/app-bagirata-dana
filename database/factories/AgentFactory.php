<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Agent;
use App\Assignment;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Agent::class, function (Faker $faker) {
    $agentName = $faker->firstName;
    return [
        "priority" => 1,
        "reassign" => false,
        "status" => true,
        "zendesk_agent_id" => $faker->randomDigit,
        "zendesk_agent_name" => $faker->name,
        "zendesk_group_id" => $faker->randomDigit,
        "zendesk_group_name" => $faker->company,
        "zendesk_custom_field_id" => strtolower($agentName),
        "zendesk_custom_field_name" => $agentName,
        "limit" => "unlimited"
    ];
});

$factory->state(Agent::class, 'unavailable', [
    'status' => false
]);

foreach (["one", "two", "three", "four", "five", "six"] as $key => $value) {
    $factory->state(Agent::class, 'agent'.Str::ucfirst($value), [
            "zendesk_agent_id" => $key + 1,
            "zendesk_agent_name" => "AGENT_".Str::upper($value)
        ]);

    $factory->state(Agent::class, 'group'.Str::ucfirst($value), [
            "zendesk_group_id" => $key + 101,
            "zendesk_group_name" => "GROUP_".Str::upper($value)
        ]);
}

$factory->afterCreatingState(Agent::class, 'assigned', function ($agent, $faker) {

    $uuid = $faker->uuid;
    factory(Assignment::class, 5)->create([
        'agent_id' => $agent->id,
        "batch_id" => $uuid
    ]);


});