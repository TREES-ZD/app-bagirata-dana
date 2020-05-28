<?php

namespace App\Jobs;

use App\Agent;
use Illuminate\Support\Arr;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
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
        $this->filters = $filters ?: [ZendeskService::AGENT_IDS => "*", ZendeskService::GROUP_IDS => "*", ZendeskService::CUSTOM_FIELD_IDS => "*"];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        $agentsByFullId = Agent::all()->keyBy('fullId');
        
        $agents = $zendesk
                    ->filterUsers($this->filters[ZendeskService::AGENT_IDS])
                    ->filterGroups($this->filters[ZendeskService::GROUP_IDS])
                    ->filterCustomFields($this->filters[ZendeskService::CUSTOM_FIELD_IDS])
                    ->getPossibleAgents();
        
        $agents = $agents
                  ->map(function($agent) use ($agentsByFullId) {
                    $id = $agent["full_id"];
                    $existingAgent = $agentsByFullId->get($id);

                    $agent = Arr::except($agent, ['full_id']);
                    
                    return $agent + [
                        "limit" => $existingAgent['limit'] ?: "unlimited",
                        "status" => $existingAgent['status'] ?: false,
                        "reassign" => $existingAgent['reassign'] ?: false
                    ];
                });
        
        DB::transaction(function() use ($agents) {
            Agent::truncate();                
            Agent::insert($agents->toArray());
        });

        Artisan::call('modelCache:clear', ['--model' => Agent::class]);
    }
}
