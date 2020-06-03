<?php

namespace App\Jobs;

use App\Task;
use App\Agent;
use App\Services\RoundRobinService;
use Exception;
use App\TaskLog;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Zendesk\API\HttpClient as ZendeskAPI;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $viewId;

    protected $task;

    protected $response;

    public $timeout = 800;

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
        $agents = $this->task->getAvailableAgents();
        Log::info("Processing task", ['task' => $this->task->toJson(), 'available_agents' => $agents->count()]);
        if ($agents->count() < 1) {
            return;
        }

        $tickets = $zendesk->getAssignableTicketsByView($this->viewId);

        $agents = $agents->sortBy(function($a) {
            return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 1;
        })->values();

        $assignments = $this->task->createAssignments($agents, $tickets);

        $batch_id = (string) Str::uuid();
        $assignments->each(function($assignment) use ($zendesk, $batch_id) {
            $agent = $assignment->get("agent");
            $ticket = $assignment->get("ticket");

            try {
                $response = $zendesk->updateTicket($ticket->id, [
                    "assignee_id" => $agent->zendesk_agent_id,
                    "group_id" => $agent->zendesk_group_id,
                    "custom_fields" => [
                        [
                        "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                        "value" => $agent->zendesk_custom_field_id
                        ]
                    ]
                ]);
                $this->task->assignments()->create([
                    "type" => Agent::ASSIGNMENT,
                    "batch_id" => $batch_id,
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName,
                    "zendesk_ticket_id" => $ticket->id,
                    "zendesk_ticket_subject" => $ticket->subject,
                    "group_id" => $agent->zendesk_group_id,
                    "response_status" => "200"
                ]);
            } catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
                Log::error((array) $e);

                $this->task->assignments()->create([
                    "type" => Agent::ASSIGNMENT,
                    "batch_id" => $batch_id,
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName,
                    "zendesk_ticket_id" => $ticket->id,
                    "zendesk_ticket_subject" => $ticket->subject,
                    "group_id" => $agent->zendesk_group_id,
                    "response_status" => 400
                ]);
            }            
        });
       
    }

    public function getResponse()
    {
        return $this->response;
    }
}
