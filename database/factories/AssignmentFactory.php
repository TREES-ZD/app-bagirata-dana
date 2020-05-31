<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Agent;
use App\Assignment;
use Faker\Generator as Faker;

$factory->define(Assignment::class, function (Faker $faker) {
    return [
        "type" => Agent::ASSIGNMENT,
        "zendesk_view_id" => $faker->randomDigit,
        "batch_id" => $faker->randomDigit,
        "agent_id" => 1,
        "agent_name" => "Andi",
        "ticket_id" => 1,
        "ticket_name" => "ticket_one",
        "response_status" => "200"
    ];
});
