<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table("agents")->insert([
            "id" => "360278992236-360000349636-1234:siang",
            "priority" => 1,
            "status" => true,
            "zendesk_agent_id" => 360278992236,
            "zendesk_agent_name" => "Eldien",
            "zendesk_group_id" => 360000349636,
            "zendesk_group_name" => "Support",
            "zendesk_custom_field" => "1234:siang",
            "limit" => "1 in 2"
        ]);                
        DB::table("agents")->insert([
            "id" => "360278992296-360000349636-1234:siang",
            "priority" => 1,
            "status" => true,
            "zendesk_agent_id" => 360278992296,
            "zendesk_agent_name" => "Norman",
            "zendesk_group_id" => 360000349636,
            "zendesk_group_name" => "Support",
            "zendesk_custom_field" => "1234:siang",
            "limit" => "1 in 2"
        ]);
        DB::table("agents")->insert([
            "id" => "360278992296-360000974835-213:test",
            "priority" => 1,
            "status" => true,
            "zendesk_agent_id" => 360278992296,
            "zendesk_agent_name" => "Norman",
            "zendesk_group_id" => 360000974835,
            "zendesk_group_name" => "Tester",
            "zendesk_custom_field" => "1234:siang",
            "limit" => "1 in 2"
        ]);        
        DB::table("agents")->insert([
            "id" => "360278992236-360000974835-123:siang",
            "priority" => 1,
            "status" => true,
            "zendesk_agent_id" => 360278992236,
            "zendesk_agent_name" => "Eldien",
            "zendesk_group_id" => 360000974835,
            "zendesk_group_name" => "Tester",
            "zendesk_custom_field" => "1234:siang",
            "limit" => "1 in 2"
        ]);
    }
}
