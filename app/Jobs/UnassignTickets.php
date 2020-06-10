<?php

namespace App\Jobs;

use App\Agent;
use Exception;
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

    public $timeout = 800;
    
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
        $unnasignedTickets = $this->agent->getUnassignedTickets()->chunk(100);
        $unnasignedTickets->each(function($tickets) use ($zendesk) {

            $unnasignedTicketsByTicketId = $tickets->keyBy('zendesk_ticket_id');
            
            $tickets = $zendesk->getTicketsByIds($tickets->pluck('zendesk_ticket_id')->all());
      

            foreach ($tickets as $i => $ticket) {
                $type = Str::upper("already_" . $ticket->status);

                if (in_array($ticket->status, ["new", "open", "pending"])) {
                    $type = Agent::UNASSIGNMENT;
                    
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
                }
    
                $this->agent->assignments()->create([
                    "type" => $type,
                    "zendesk_view_id" => $unnasignedTicketsByTicketId->get($ticket->id)->zendesk_view_id,
                    "batch_id" => $unnasignedTicketsByTicketId->get($ticket->id)->batch_id,
                    "agent_id" => $this->agent->id,
                    "agent_name" => $this->agent->fullName,
                    "zendesk_ticket_id" => $ticket->id,
                    "zendesk_ticket_subject" => $ticket->subject,
                    "group_id" => $this->agent->zendesk_group_id,
                    "response_status" => 200
                ]);
            }
    
        });
        
    }

    public function failed(Exception $exception)
    {
        logs()->error($exception->getMessage());
    }
}
