<?php

namespace App\Jobs;

use App\Task;
use App\Agent;
use Exception;
use App\TaskLog;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Events\TicketsProcessed;
use App\Services\ZendeskService;
use App\Services\RoundRobinService;
use Illuminate\Support\Facades\Log;
use App\Events\AssignmentsProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
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
        Log::info("Processing task");

        $agents = $this->task->getAvailableAgents();
        if ($agents->count() < 1) {
            return;
        }

        $tickets = $zendesk->getAssignableTicketsByView($this->viewId);

        $agents = $agents->sortBy(function($a) {
            return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 1;
        })->values();

        $assignments = $this->task->createAssignments($agents, $tickets);

        $assignments->chunk(100)->each(function($assignments) use ($zendesk) {
            $tickets = $assignments->map(function($assignment) {
                $agent = $assignment->get("agent");
                $ticket = $assignment->get("ticket");
                
                return [
                    "id" => $ticket->id,
                    "assignee_id" => $agent->zendesk_agent_id,
                    "group_id" => $agent->zendesk_group_id,
                    "custom_fields" => [
                        [
                        "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                        "value" => $agent->zendesk_custom_field_id
                        ]
                    ]
                ];
            });

            $response = $zendesk->updateManyTickets($tickets->values()->all());

            event(new AssignmentsProcessed($response->job_status, $assignments->values()->all(), $this->viewId));
        });

    }
}
