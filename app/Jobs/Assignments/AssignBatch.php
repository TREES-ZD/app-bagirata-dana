<?php

namespace App\Jobs\Assignments;

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
use App\Repositories\AgentRepository;
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

class AssignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $viewId;

    protected $taskIds;

    protected $batch;

    protected $response;

    public $timeout = 800;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($tasks)
    {
        $this->batch = (string) Str::uuid();
        $this->taskIds = $tasks->pluck('id')->all();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignmentRepository $assignmentRepository)
    {   
        $tasks = Task::whereIn('id', $this->taskIds)->get();
        $assignments = $assignmentRepository->prepareAssignment($this->batch, $tasks);

        $jobStatuses = $assignments->update();

        if ($jobStatuses->count() > 0) {
            CheckJobStatuses::dispatch($this->batch, $jobStatuses->ids()->all())->onQueue('assignment-job');
        }
        
    }
}
