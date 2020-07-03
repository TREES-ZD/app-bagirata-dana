<?php

namespace App\Jobs\Agent;

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
use App\Jobs\Agent\LogUnassignments;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Events\UnassignmentsProcessed;
use App\Repositories\AssignmentRepository;
use App\Repositories\TicketRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\ClientException;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Zendesk\API\Exceptions\ApiResponseException;

class UnassignTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $agent;

    private $batch;

    public $timeout = 1200;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($agent)
    {
        $this->agent = $agent;
        $this->batch = (string) Str::uuid();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TicketRepository $ticketRepository, AssignmentRepository $assignmentRepository)
    {
        $assignedTickets = $ticketRepository->getAssigned($this->agent);
        
        $unassignments = $assignmentRepository->prepareUnassignment($this->batch, $this->agent, $assignedTickets);

        $unassignments->chunk(100)->each(function($assignments) use ($ticketRepository) {
            $response = $ticketRepository->zendesk->unassignTickets($assignments->ticketIds()->all(), $this->agent->zendesk_agent_id, $this->agent->fullName);
            
            CheckUnassignedTickets::dispatch($this->batch, $response->job_status->id, $assignments->ticketIds()->all())->onQueue('unassignment-job');            
        });        
        
    }

    public function failed(Exception $exception)
    {
        if ($exception instanceof ClientException) {
            logs()->info("Rate limit exceeded");
            logs()->error($exception->getMessage());
        }
        logs()->error($exception->getMessage());
    }
}
