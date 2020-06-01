<?php

use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("tasks")->insert([
            "zendesk_view_id" => "360001440115",
            "zendesk_view_title" => "Ticket-ticket untuk dibagi rata",
            "zendesk_view_position" => 100,
            "interval" => "everyMinute",
            "group_id" => "360000349636",
            "limit" => "unlimited",
            "enabled" => true
        ]);  
    }
}
