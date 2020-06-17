<?php

namespace App\Jobs;

use App\Agent;
use Exception;
use App\Assignment;
use App\AvailabilityLog;
use App\Events\UnassignmentsProcessed;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
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
            $tickets = $zendesk->getTicketsByIds($ticketIds->values()->all());

            $unassignableTickets = collect($tickets)->unassignableTickets();
            
            $unavailableTickets = $ticketIds->diff(collect($tickets)->pluck('id'));

            // Remove unavailable tickets (ticket that might be deleted in Zendesk)
            if ($unavailableTicketsCount = Redis::srem(sprintf("agent:%s:assignedTickets", $this->agent->id), ...$unavailableTickets->values()->all())) {
                logs()->debug($unavailableTicketsCount);
            }
            
            $response = $zendesk->unassignTickets($unassignableTickets->pluck('id')->values()->all(), $this->agent->zendesk_agent_id, $this->agent->fullName);

            event(new UnassignmentsProcessed(optional($response)->job_status, $this->agent, $tickets));
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
