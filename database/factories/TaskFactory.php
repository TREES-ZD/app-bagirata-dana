<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Task;
use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {
    return [
        'zendesk_view_id' => "360001440115",
        'zendesk_view_title' => 1,
        'zendesk_view_position' => $faker->randomDigitNotNull,
        'interval' => "everyMinute",
        'group_id' => 10,
        'limit' => "unlimited",
        'enabled' => true,
    ];
});
