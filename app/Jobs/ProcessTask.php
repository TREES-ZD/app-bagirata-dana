<?php

namespace App\Jobs;

use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Agent;
use App\Task;
use App\TaskLog;

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
    public function __construct(Task $task)
    {
        $this->task = $task;
        $this->viewId = $task->zendesk_view_id;
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
        
        $tickets = $client->views($this->viewId)->tickets();
        
        // Assign round robin
        // $agents = Agent::where('status', true)->get();
        $agents = Agent::disableCache()->where('status', true)
                ->with(['assignments'])
                ->get()
                ->sortBy(function($a) {
                    return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 0;
                });

        $sortedAgents = collect();
        $agents->map(function($a) use ($sortedAgents) {
            $sortedAgents->push($a);
        });
        $totalAgents = $agents->count();
        $totalTickets = count($tickets->tickets);
        
        if ($totalAgents < 1) return;

        foreach ($tickets->tickets as $i => $ticket) {
            $agentNum = ($i % $totalAgents);
            $agent = $sortedAgents[$agentNum];
            $client->tickets()->update($ticket->id, [
                "assignee_id" => $agent->zendesk_agent_id,
                "group_id" => $agent->zendesk_group_id,
                "custom_fields" => [
                    [
                    "id" => 360000282796,
                    "value" => $agent->zendesk_custom_field_id
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
        
        TaskLog::create([
            'task_id' => $this->task->id,
            'causer_type' => "SYSTEM",
            'total_assignments' => $totalTickets
        ]);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
