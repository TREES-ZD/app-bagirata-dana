<?php

namespace App\Jobs\Task;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Repositories\AgentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\AssignmentRepository;
use App\Repositories\TicketRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogAssignments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batch;

    protected $checkedTicketIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, $checkedTicketIds)
    {
        $this->batch = $batch;
        $this->checkedTicketIds = $checkedTicketIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AgentRepository $agentRepository, AssignmentRepository $assignmentRepository, TicketRepository $ticketRepository)
    {        
        $preparedAssignments = $assignmentRepository->getPrepared($this->batch)->whereIn('ticket_id', $this->checkedTicketIds);
        $updatedTickets = $ticketRepository->find($this->checkedTicketIds);
        
        $assignments = $preparedAssignments->reconcileAssignment($updatedTickets);

        $agentRepository->updateAssignment($assignments);

        $assignments->logs();
    }
}
