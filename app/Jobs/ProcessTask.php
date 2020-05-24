<?php

namespace App\Jobs;

use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Agent;
use App\Services\ZendeskService;
use App\Task;
use App\TaskLog;

class ProcessTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $viewId;

    protected $task;

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
    public function handle(ZendeskService $zendesk)
    {
        $group_id = $this->task->group_id;

        $tickets = $zendesk->getTicketsByView($this->viewId);
        $filteredTickets = collect($tickets)
                            ->filter(function($ticket) use ($group_id) {
                                return !$ticket->group_id || $ticket->group_id == $group_id;
                            });  
        
        // Assign round robin
        // $agents = Agent::where('status', true)->get();
        $agents = $this->task
                        ->rules()
                        ->disableCache()
                        ->where('status', true)
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
        // dd($filteredTickets, $agents, $totalAgents);

        if ($totalAgents < 1) return;

        foreach ($filteredTickets as $i => $ticket) {
            $agentNum = ($i % $totalAgents);
            $agent = $sortedAgents[$agentNum];
            $zendesk->updateTicket($ticket->id, [
                "assignee_id" => $agent->zendesk_agent_id,
                "group_id" => $agent->zendesk_group_id,
                "custom_fields" => [
                    [
                    "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
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
        
        // TaskLog::create([
        //     'task_id' => $this->task->id,
        //     'causer_type' => "SYSTEM",
        //     'total_assignments' => $totalTickets
        // ]);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
