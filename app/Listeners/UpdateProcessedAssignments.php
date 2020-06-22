<?php

namespace App\Listeners;

use App\Agent;
use Exception;
use App\Assignment;
use Illuminate\Support\Str;
use App\Jobs\LogAssignments;
use App\Events\TicketsProcessed;
use App\Services\ZendeskService;
use Huddle\Zendesk\Facades\Zendesk;
use App\Events\AssignmentsProcessed;
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateProcessedAssignments implements ShouldQueue
{
    protected $zendesk;

    public $retryAfter = 60;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ZendeskService $zendesk)
    {
        $this->zendesk = $zendesk;
    }

    /**
     * Handle the event.
     *
     * @param  TicketsProcessed  $event
     * @return void
     */
    public function handle(AssignmentsProcessed $event)
    {   
        $jobStatus = $event->jobStatus;
        $batchId = $event->batchId;
        $viewId = $event->viewId;
        
        while (true) {
            sleep(10);
            $response = $this->zendesk->getJobStatus($jobStatus->id);

            if ($response->job_status->status == "completed") {
                $ticketResults = collect($response->job_status->results)->groupBy('id');

                dispatch_now(new LogAssignments($batchId));
                return;
            }

        }        
    }
}
