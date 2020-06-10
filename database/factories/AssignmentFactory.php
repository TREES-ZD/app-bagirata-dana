<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Agent;
use App\Assignment;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        "type" => Agent::ASSIGNMENT,
        "zendesk_view_id" => $faker->randomNumber,
        "batch_id" => "some-uuid-some-uuid",
        "agent_id" => $faker->randomDigit,
        "agent_name" => sprintf("%s (%s, %s)", $faker->name, $faker->company, $faker->name),
        "zendesk_ticket_id" => $faker->unique()->randomDigit,
        "zendesk_ticket_subject" => $faker->sentence(),
        "response_status" => "200"
    ];
});

$factory->state(Assignment::class, 'unassignment', [
    'type' => Agent::UNASSIGNMENT,
]);

$factory->state(Assignment::class, 'already_solved', [
    'type' => "ALREADY_SOLVED",
]);