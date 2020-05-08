<?php

namespace App\Jobs;

use App\Agent;
use App\Assignment;
use App\AvailabilityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Zendesk\API\Exceptions\ApiResponseException;

class UnassignTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public function handle()
    {
        $subdomain = env("ZENDESK_SUBDOMAIN", "contreesdemo11557827937");
        $username  = env("ZENDESK_USERNAME", "eldien.hasmanto@treessolutions.com");
        $token     = env("ZENDESK_TOKEN", "2HJtvL35BSsWsVR4b3ZCxvYhLGYcAacP2EyFKGki"); // replace this with your token
        
        $client = new ZendeskAPI($subdomain);
        $client->setAuth('basic', ['username' => $username, 'token' => $token]);

        $agent_id = $this->agent->id;
        $latest_log = AvailabilityLog::where("agent_id", $agent_id)->where("status", "Available")->latest()->first(); //TODO: check if not exist
        $ticket_ids = Assignment::where('agent_id', $agent_id)->where('type', Agent::ASSIGNMENT)->whereDate('created_at', ">=", $latest_log->created_at)->get()->pluck('ticket_id');

        $tickets = null;
        try {
            $response = $client->tickets()->findMany($ticket_ids->toArray());
            $tickets = $response->tickets;
        } catch (ApiResponseException $apiException) {
            Log::error($apiException);
            return;
        }

        //Unassign
        // $client->tickets()->updateMany([
        //     "ids" => array_column($tickets, "id"),
        //     "assignee_id" => null,
        //     "group_id" => $this->agent->zendesk_group_id,
        //     "custom_fields" => [
        //         [
        //         "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
        //         "value" => null
        //         ]
        //     ],
        //     "additional_tags" => ["bagirata_agent_unavailable"],
        //     "comment" =>  [
        //         "body" => "BagiRata Agent Unavailable: " . $this->agent->fullName,
        //         "public" => false
        //     ]
        // ]);
        foreach ($tickets as $i => $ticket) {
            $client->tickets()->update($ticket->id, [
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
                "agent_name" => $this->agent->fullName,
                "ticket_id" => $ticket->id,
                "ticket_name" => $ticket->subject,
                "group_id" => $this->agent->zendesk_group_id
            ]);
        }

    }
}
