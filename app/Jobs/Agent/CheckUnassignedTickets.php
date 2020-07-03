<?php

namespace App\Jobs\Agent;

use Illuminate\Bus\Queueable;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\Cache;
use App\Repositories\TicketRepository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckUnassignedTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $batch;

    public $jobStatusId;
    public $ticketIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($batch, $jobStatusId, $ticketIds)
    {
        $this->batch = $batch;
        $this->jobStatusId = $jobStatusId;
        $this->ticketIds = $ticketIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TicketRepository $ticketRepository)
    {
        $tickets = $ticketRepository->find($this->ticketIds);
        
        sleep(5);
        while (1) {
            $updatedTickets = $tickets->checkForUpdate();

            if ($updatedTickets->areAllUnassigned()) {
                LogUnassignments::dispatch($this->batch, $this->ticketIds)->onQueue('unassignment-job');
                return;
            }

            sleep(10);
        }    
    }

    public function failed() {
        LogUnassignments::dispatch($this->batch, $this->ticketIds)->onQueue('unassignment-job');
    }
}
