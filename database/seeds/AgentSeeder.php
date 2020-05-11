<?php

use App\Agent;
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
        Agent::insert([
            "priority" => 1,
            "reassign" => false,
            "status" => true,
            "zendesk_agent_id" => 360278992236,
            "zendesk_agent_name" => "Poc1demo",
            "zendesk_group_id" => 360000349636,
            "zendesk_group_name" => "Support",
            "zendesk_custom_field_id" => "siang",
            "zendesk_custom_field_name" => "Siang",
            "limit" => "unlimited"
        ]);                
        Agent::insert([
            "priority" => 1,
            "reassign" => false,
            "status" => true,
            "zendesk_agent_id" => 360278992296,
            "zendesk_agent_name" => "Edi Salome",
            "zendesk_group_id" => 360000349636,
            "zendesk_group_name" => "Support",
            "zendesk_custom_field_id" => "siang",
            "zendesk_custom_field_name" => "Siang",
            "limit" => "unlimited"
        ]);
        Agent::insert([
            "priority" => 1,
            "reassign" => false,
            "status" => true,
            "zendesk_agent_id" => 360278992296,
            "zendesk_agent_name" => "Edi Salome",
            "zendesk_group_id" => 360000974835,
            "zendesk_group_name" => "Tester",
            "zendesk_custom_field_id" => "malam",
            "zendesk_custom_field_name" => "Malam",
            "limit" => "unlimited"
        ]);        
        Agent::insert([
            "priority" => 1,
            "reassign" => false,
            "status" => true,
            "zendesk_agent_id" => 360278992236,
            "zendesk_agent_name" => "Poc1demo",
            "zendesk_group_id" => 360000974835,
            "zendesk_group_name" => "Tester",
            "zendesk_custom_field_id" => "malam",
            "zendesk_custom_field_name" => "Malam",
            "limit" => "unlimited"
        ]);
    }
}
