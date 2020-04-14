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
            "id" => "some-unique-uuid",
            "zendesk_view_id" => "360001440115",
            "zendesk_view_title" => "Ticket-ticket untuk dibagi rata",
            "interval" => "everyMinute",
            "assign_to_agent" => "default",
            "limit" => "1 in 2",
            "enabled" => true
        ]);  
    }
}
