<?php

namespace App\Jobs;

use App\Agent;
use App\Assignment;
use App\AvailabilityLog;
use App\Services\ZendeskService;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
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
        $agent_id = $this->agent->id;
        $latest_log = AvailabilityLog::where("agent_id", $agent_id)->where("status", "Available")->latest()->first(); //TODO: check if not exist
        $assignments_builder = Assignment::where('agent_id', $agent_id)->where('type', Agent::ASSIGNMENT)->whereDate('created_at', ">=", $latest_log->created_at);
        $ticket_ids = $assignments_builder->get()->pluck('ticket_id');
        $view_by_ticket_id = $assignments_builder->get()->pluck('zendesk_view_id', 'ticket_id');

        $tickets = null;
        try {
            $tickets = $zendesk->getTicketsByIds($ticket_ids->toArray());
        } catch (ApiResponseException $apiException) {
            Log::error($apiException);
            return;
        }

        $batch_id = (string) Str::uuid();
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
                "zendesk_view_id" => $view_by_ticket_id->get($ticket->id),
                "batch_id" => $batch_id,
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
