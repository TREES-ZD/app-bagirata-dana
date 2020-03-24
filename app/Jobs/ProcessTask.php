<?php

namespace App\Jobs;

use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Agent;

class ProcessTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $viewId;

    protected $response;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $viewId)
    {
        $this->viewId = $viewId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $subdomain = "contreesdemo11557827937";
        $username  = "eldien.hasmanto@treessolutions.com";
        $token     = "2HJtvL35BSsWsVR4b3ZCxvYhLGYcAacP2EyFKGki"; // replace this with your token
        
        $client = new ZendeskAPI($subdomain);
        $client->setAuth('basic', ['username' => $username, 'token' => $token]);
        
        $tickets = $client->views($this->viewId)->tickets();
        
        // Assign round robin
        $agents = Agent::where('status', true)->get();
        
        $totalAgents = $agents->count();
        $totalTickets = count($tickets->tickets);
    
        foreach ($tickets->tickets as $i => $ticket) {
            $agentNum = ($i % $totalAgents);
            $agent = $agents[$agentNum];
            $client->tickets()->update($ticket->id, [
                "assignee_id" => $agent->zendesk_agent_id,
                "group_id" => $agent->zendesk_group_id,
                "custom_fields" => [
                    [
                    "id" => 360000299575,
                    "value" => $agent->zendesk_custom_field
                    ]
                ]
            ]);
            $agent->assignments()->create([
                "type" => Agent::ASSIGNMENT,
                "agent_name" => $agent->fullName,
                "ticket_id" => $ticket->id,
                "ticket_name" => $ticket->subject,
                "group_id" => $agent->zendesk_group_id
            ]);
        }

        $this->response = "hallo";
    }

    public function getResponse()
    {
        return $this->response;
    }
}
