<?php

namespace App\Jobs;

use App\Agent;
use App\Assignment;
use App\AvailabilityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Zendesk\API\Exceptions\ApiResponseException;

class UnassignTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $agent;

    public $timeout = 120;
    
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
        $unnasignedTickets = $this->agent->getUnassignedTickets();
        $unnasignedTicketsByTicketId = $unnasignedTickets->keyBy('ticket_id');
        try {
            $tickets = $zendesk->getTicketsByIds($unnasignedTickets->pluck('ticket_id')->toArray());
        } catch (ApiResponseException $apiException) {
            Log::error($apiException);
            return;
        }

        foreach ($tickets as $i => $ticket) {
            $zendesk->updateTicket($ticket->id, [
                "custom_fields" => [
                    [
                    "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                    "value" => null
                    ]
                ],
                "tags" => array_merge($ticket->tags, ["bagirata_agent_unavailable"]),
                "comment" =>  [
                    "body" => "BAGIRATA Agent Unavailable: " . $this->agent->fullName,
                    "author_id" => $this->agent->zendesk_agent_id,
                    "public" => false
                ]
            ]);
            $this->agent->assignments()->create([
                "type" => Agent::UNASSIGNMENT,
                "zendesk_view_id" => $unnasignedTicketsByTicketId->get($ticket->id)->zendesk_view_id,
                "batch_id" => $unnasignedTicketsByTicketId->get($ticket->id)->batch_id,
                "agent_id" => $this->agent->id,
                "agent_name" => $this->agent->fullName,
                "ticket_id" => $ticket->id,
                "ticket_name" => $ticket->subject,
                "group_id" => $this->agent->zendesk_group_id,
                "response_status" => 200
            ]);
        }

    }
}
