<?php

namespace App\Jobs;

use App\Agent;
use App\Services\ZendeskService;
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

    private $filters;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filters = null)
    {
        $this->filters = $filters ?: ["zendesk_agent_ids" => null, "zendesk_group_ids" => null, "zendesk_custom_field_ids" => null];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        $agentByKey = collect($zendesk->getUsers(null, false, $this->filters['zendesk_agent_ids'])); //By Key
        $groupByKey = collect($zendesk->getGroups(null, false, $this->filters['zendesk_group_ids'])); //By Key
        $customFields = collect($zendesk->getCustomFields(null, false, $this->filters['zendesk_custom_field_ids'])); //By Key
        $groupMemberships = collect($zendesk->getGroupMemberships());

        $existingAgentsByIdentifier = Agent::all()->keyBy(function($agent) {
            // Identify agent based on the pattern ':zendesk_agent_id-:zendesk_group_id-:zendesk_custom_field_id' 
            return sprintf("%s-%s-%s", $agent->zendesk_agent_id, $agent->zendesk_group_id, $agent->zendesk_custom_field_id);
        });

        $agents = $groupMemberships
                ->crossJoin($customFields)
                ->reject(function($membershipAndCustomField) use ($agentByKey, $groupByKey) {
                    $membership = $membershipAndCustomField[0];
                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);
                    
                    return !($agent && $group);
                })
                ->map(function($membershipAndCustomField) use ($agentByKey, $groupByKey, $existingAgentsByIdentifier) {
                    $membership = $membershipAndCustomField[0];
                    $customField = $membershipAndCustomField[1];

                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);

                    $id = sprintf("%s-%s-%s", $agent->id, $group->id, $customField->value);
                    $existingAgent = $existingAgentsByIdentifier->get($id);
                    return [
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
