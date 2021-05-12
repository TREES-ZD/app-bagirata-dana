<?php

namespace App\Services\Zendesk;

use Illuminate\Support\Collection;
use App\Agent;
use App\Collections\AgentCollection;
use App\Services\Zendesk\TicketCollection;
use App\Services\Assignments\PreparedAssignmentCollection;

class TicketService
{   
    /**
     * @var ZendeskWrapper
     */
    protected $wrapper;

    public function __construct(ZendeskWrapper $wrapper)
    {
        $this->wrapper = $wrapper;
    }

    /**
     * Only update the assignments
     *
     * @param PreparedAssignmentCollection $assignments
     * @return JobStatusCollection
     */
    public function assign(PreparedAssignmentCollection $assignments): JobStatusCollection
    {
        $jobStatuses = $assignments->chunk(100)
                                    ->map(function(PreparedAssignmentCollection $assignments) {
                                        $response = $this->wrapper->updateManyTickets($assignments->toBody());
                                        return new JobStatus($response->job_status);
                                    })
                                    ->flatten();

        return new JobStatusCollection($jobStatuses->values()->all());
    }

    public function assignableByViews(Collection $tasks): TicketCollection
    {
        $tickets = $tasks->sortBy('zendesk_view_position')
                          ->pluck('zendesk_view_id')
                          ->map(function($viewId) use ($tasks) {
                                return $this->assignableByView($viewId)->each(function($ticket) use ($viewId, $tasks) {
                                    $ticket->task = $tasks->firstWhere('zendesk_view_id', $viewId);
                                });
                            })
                          ->flatten();
        
                          return new TicketCollection($tickets->unique->id->all());
    }

    
    /**
     * get assignable tickets by view, limit to 500 tickets
     *
     * @param [type] $viewId
     * @return TicketCollection
     */
    public function assignableByView($viewId): TicketCollection
    {
        $tickets = TicketCollection::make();
        $page = 1;
        while ($page && $page <= 5 || $page > 2 && $tickets->isEmpty()) {
            $response = $this->wrapper->listTicketsByView($viewId, ['page' => $page]);
            
            collect($response->tickets)->filterMap(function($ticket) {
                $ticket = new Ticket($ticket);
                return $ticket->isAssignable() ? $ticket : null;
            })
            ->each(function(Ticket $ticket) use ($tickets) {
                $tickets->push($ticket);
            });

            $response->next_page ? $page++ : $page = null;
        }

        return $tickets;
    }

    public function getObserved(): TicketCollection
    {
        $tickets = TicketCollection::make();
        $page = 1;
        while ($page && $page <= 5) {
            $response = $this->wrapper->search("tags:bagirata_observe status<solved", ['page' => $page]);
            
            collect($response->results)
            ->each(function(Ticket $ticket) use ($tickets) {
                $tickets->push($ticket);
            });

            $response->next_page ? $page++ : $page = null;
        }

        return $tickets;
    }

    public function getAssignedByAgents(AgentCollection $agents): TicketCollection
    {
        $tickets = $agents->map(function(Agent $agent) {
                                return $this->getAssignedByAgent($agent)->values()->all();
                            })
                            ->flatten();
        
        return new TicketCollection($tickets->values()->unique->id->all());
    }

    public function getAssignedByAgent(Agent $agent): TicketCollection 
    {
        $tickets = TicketCollection::make();
        $page = 1;
        while ($page && $page <= 10) {
            $response = $this->wrapper->search("type:ticket assignee:$agent->zendesk_agent_id group:$agent->zendesk_group_id tags:$agent->zendesk_custom_field_id status<solved", ['page' => $page]);
            
            collect($response->results)
            ->each(function($ticket) use ($tickets) {
                $tickets->push(new Ticket($ticket));
            });

            $response->next_page ? $page++ : $page = null;
        }

        return $tickets;
    }
}