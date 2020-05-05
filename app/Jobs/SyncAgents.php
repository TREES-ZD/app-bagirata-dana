<?php

namespace App\Jobs;

use App\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Queue\InteractsWithQueue;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncAgents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $type = "admin";
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subdomain = env("ZENDESK_SUBDOMAIN", "contreesdemo11557827937");
        $username  = env("ZENDESK_USERNAME", "eldien.hasmanto@treessolutions.com");
        $token     = env("ZENDESK_TOKEN", "2HJtvL35BSsWsVR4b3ZCxvYhLGYcAacP2EyFKGki"); // replace this with your token
        
        $client = new ZendeskAPI($subdomain);
        $client->setAuth('basic', ['username' => $username, 'token' => $token]);

        $response = $client->search()->find("type:user role:$this->type role:agent", ['sort_by' => 'updated_at']);
        $agentByKey = collect($response->results)->keyBy("id");

        $response = $client->groups()->findAll();
        $groupByKey = collect($response->groups)->keyBy("id");

        $response = $client->ticketFields()->find(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796));
        $customFields = collect($response->ticket_field->custom_field_options)->keyBy('id');
        $existingAgentsByKey = Agent::all()->keyBy('id');

        $response = $client->groupMemberships()->findAll();
        $agents = collect($response->group_memberships)
                ->crossJoin($customFields)
                ->reject(function($membershipAndCustomField) use ($agentByKey, $groupByKey) {
                    $membership = $membershipAndCustomField[0];
                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);
                    
                    return !($agent && $group);
                })
                ->map(function($membershipAndCustomField) use ($agentByKey, $groupByKey, $existingAgentsByKey) {
                    $membership = $membershipAndCustomField[0];
                    $customField = $membershipAndCustomField[1];

                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);

                    $id = sprintf("%s-%s-%s", $agent->id, $group->id, $customField->value);
                    $existingAgent = $existingAgentsByKey->get($id);
                    return [
                        "id" => $id,
                        "priority" => 1,
                        "zendesk_agent_id" => $agent->id,
                        "zendesk_agent_name" => $agent->name,
                        "zendesk_group_id" => $group->id,
                        "zendesk_group_name" => $group->name,
                        "zendesk_custom_field_id" => $customField->value,
                        "zendesk_custom_field_name" => $customField->name,
                        "limit" => $existingAgent['limit'] ?: "unlimited",
                        "status" => $existingAgent['status'] ?: false,
                        "reassign" => $existingAgent['reassign'] ?: false
                    ];
                });
        
        DB::transaction(function() use ($agents) {
            Agent::truncate();                
            Agent::insert($agents->toArray());
        });

        Artisan::call('modelCache:clear',['--model' => Agent::class]);
    }
}
