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

    public $timeout = 1200;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($agent)
    {
        $this->agent = $agent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ZendeskService $zendesk)
    {
        $assignedTicketIds = Redis::smembers(sprintf("agent:%s:assignedTickets", $this->agent->id));
        collect($assignedTicketIds)->chunk(100)->each(function($ticketIds) use ($zendesk) {
            $batchId = (string) Str::uuid();

            $tickets = collect(Cache::remember(sprintf("tickets:%s", $batchId), 3000, function() use ($zendesk, $ticketIds) {
                return $zendesk->getTicketsByIds($ticketIds->values()->all());
            }));

            $filterCallback = function ($ticket) {
                return in_array($ticket->status, ["new", "open", "pending"]); //TODO: check if assignee is still the agent
            };
            $unassignableTickets = $tickets->filter($filterCallback);
            $nonUnassignableTickets = $tickets->reject($filterCallback);

            $unavailableTickets = $ticketIds->diff(collect($tickets)->pluck('id'));

            // Remove unavailable tickets (ticket that not new)
            if ($nonUnassignableTicketsCount = Redis::srem(sprintf("agent:%s:assignedTickets", $this->agent->id), ...$nonUnassignableTickets->values()->all())) {
                logs()->debug($nonUnassignableTicketsCount);
            }

            // Remove unavailable tickets (ticket that might already be deleted)
            if ($unavailableTicketsCount = Redis::srem(sprintf("agent:%s:assignedTickets", $this->agent->id), ...$unavailableTickets->values()->all())) {
                logs()->debug($unavailableTicketsCount);
            }

            if ($unassignableTickets->count() > 0) {
                $response = $zendesk->unassignTickets($unassignableTickets->pluck('id')->values()->all(), $this->agent->zendesk_agent_id, $this->agent->fullName);

                CheckJobStatus::withChain([
                    (new LogUnassignments($batchId, $this->agent))->onQueue('unassignment-job'),
                ])->dispatch($batchId, $response->job_status->id)->onQueue('unassignment-job'); 
            }
            
            // event(new UnassignmentsProcessed(optional($response)->job_status, $this->agent, $batchId));
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
