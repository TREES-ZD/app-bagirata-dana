<?php

namespace App\Jobs\Assignments;

use App\Task;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Assignments\AssignmentService;

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
    public function handle(AssignmentService $assignmentService)
    {   
        $tasks = Task::find($this->taskIds);
        
        $jobStatuses = $assignmentService->assignBatch($this->batch, $tasks);

        if ($jobStatuses->isNotEmpty()) {
            CheckJobStatuses::dispatch($this->batch, $jobStatuses->ids()->all())->onQueue('assignment-job');
        }
    }
}
