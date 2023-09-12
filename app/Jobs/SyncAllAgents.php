<?php

namespace App\Jobs;

use App\Models\Agent;
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

class SyncAllAgents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $type = "admin";

    private $filters;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        $existingAgents = Agent::disableCache()->all()->keyBy('fullId');
        
        $zendesk->refresh();

        $possibleAgents = $zendesk->filterUsers("*")
                            ->filterGroups("*")
                            ->filterCustomFields("-")
                            ->getPossibleAgents();

        $agents = $possibleAgents
                    ->map(function($agent, $fullId) use ($existingAgents) {
                        $existingAgent = optional($existingAgents->get($fullId))->toArray()  ?? [];

                        return array_merge(
                            Arr::except($existingAgent, ['fullId', 'fullName']),
                            [
                                "priority" => 1,
                                "limit" =>  "unlimited",
                                "status" =>  false,
                                "reassign" =>  false
                            ], 
                            $agent
                        );
                    });

        DB::transaction(function() use ($agents) {
            Agent::upsert($agents->whereNotNull('id')->toArray(), 'id'); //existing
            Agent::insert($agents->whereNull('id')->toArray());
        });

        Artisan::call('modelCache:clear', ['--model' => Agent::class]);
    }
}
