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

        $newAgents = $zendesk->filterUsers("*")
                            ->filterGroups("*")
                            ->filterCustomFields("-")
                            ->getPossibleAgents();

        $agents = $newAgents->diffKeys($existingAgents)
                            ->map(function($agent) {
                                $agent = Arr::except($agent, ['full_id']);
                                return $agent + [
                                    "priority" => 1,
                                    "limit" =>  "unlimited",
                                    "status" =>  false,
                                    "reassign" =>  false
                                ];
                            });

        DB::transaction(function() use ($agents) {
            Agent::insert($agents->toArray());
        });

        Artisan::call('modelCache:clear', ['--model' => Agent::class]);
    }
}
