<?php

namespace App\Jobs\Assignments;

use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Collections\AgentCollection;
use App\Repositories\AgentRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\Assignments\CheckJobStatuses;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Assignments\AssignmentService;

class UnassignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    protected $agentIds;

    public $timeout = 1200;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(AgentCollection $agents = null)
    {
        $this->batch = (string) Str::uuid();
        $this->agentIds = optional($agents)->pluck('id');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AgentRepository $agentRepository, AssignmentService $assignmentService)
    {
        $agents = !$this->agentIds ? $agentRepository->getUnassignEligible() : $agentRepository->get($this->agentIds);

        $jobStatuses = $assignmentService->unassignBatch($this->batch, $agents);
        
        if ($jobStatuses->isNotEmpty()) {
            CheckJobStatuses::dispatch($this->batch, $jobStatuses->ids()->all())->onQueue('unassignment-job');        
        }
    }
}
