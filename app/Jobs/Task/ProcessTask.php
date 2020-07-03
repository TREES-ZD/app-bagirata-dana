<?php

namespace App\Jobs\Task;

use App\Task;
use App\Agent;
use Exception;
use App\TaskLog;
use Illuminate\Support\Str;
use App\Jobs\CheckJobStatus;
use Illuminate\Bus\Queueable;
use App\Events\TicketsProcessed;
use App\Services\ZendeskService;
use App\Jobs\Task\LogAssignments;
use App\Services\RoundRobinService;
use Illuminate\Support\Facades\Log;
use App\Events\AssignmentsProcessed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Repositories\TicketRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Zendesk\API\HttpClient as ZendeskAPI;
use App\Repositories\AssignmentRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Listeners\UpdateProcessedAssignments;

class ProcessTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $viewId;

    protected $task;

    protected $batch;

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
        $this->batch = (string) Str::uuid();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TicketRepository $ticketRepository, AssignmentRepository $assignmentRepository)
    {    
        $tickets = $ticketRepository->getAssignableTicketsByView($this->viewId);
        $agents = $this->task->getAvailableAgents($this->task->id);

        $assignments = $assignmentRepository->prepare($this->batch, $agents, $tickets);
            
        $assignments->chunk(100)->each(function($assignments) use ($ticketRepository) {
            $response = $ticketRepository->zendesk->updateManyTickets($assignments->toTickets(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796))->all());
            
            CheckAssignedTickets::dispatch($this->batch, $response->job_status->id, $assignments->ticketIds()->all())->onQueue('assignment-job');
        });
    }
}
