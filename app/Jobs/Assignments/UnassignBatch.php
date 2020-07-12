<?php

namespace App\Jobs\Assignments;

use App\Agent;
use Exception;
use App\Assignment;
use App\AvailabilityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Jobs\CheckJobStatus;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Log;
use App\Collections\AgentCollection;
use App\Jobs\Agent\LogUnassignments;
use App\Repositories\AgentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Events\UnassignmentsProcessed;
use App\Repositories\TicketRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\ClientException;
use Zendesk\API\HttpClient as ZendeskAPI;
use App\Jobs\Assignments\CheckJobStatuses;
use App\Repositories\AssignmentRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Zendesk\API\Exceptions\ApiResponseException;

class UnassignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    protected $agentIds;

    public $timeout = 1200;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AgentCollection $agents = null)
    {
        $this->batch = (string) Str::uuid();
        $this->agentIds = optional($agents)->pluck('id');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignmentRepository $assignmentRepository, AgentRepository $agentRepository)
    {
        $agents = !$this->agentIds ? $agentRepository->getUnassignEligible() : Agent::disableCache()->find($this->agentIds);

        $unassignments = $assignmentRepository->prepareUnassignment($this->batch, $agents);

        $unassignments->createLogs();

        $jobStatuses = $unassignments->updateUnassignment();

        if ($jobStatuses->isNotEmpty()) {
            CheckJobStatuses::dispatch($this->batch, $jobStatuses->ids()->all())->onQueue('unassignment-job');        
        }
    }
}
