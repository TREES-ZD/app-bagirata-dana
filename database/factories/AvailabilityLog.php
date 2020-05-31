<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\AvailabilityLog;
use App\Model;
use Carbon\Carbon;
use Faker\Generator as Faker;

$factory->define(AvailabilityLog::class, function (Faker $faker) {
    return [
        'id' => 1,
        "agent_id" => $faker->randomDigit,
        "agent_name" => $faker->firstName,
        "causer_id" => null,
        "causer_type" => null,
        "created_at" => Carbon::now()
    ];
});
