<?php

namespace App\Services\Assignments;

use App\Agent;
use App\Assignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Collections\AgentCollection;
use App\Repositories\AgentRepository;
use App\Services\Zendesk\TicketService;
use App\Collections\AssignmentCollection;
use App\Repositories\AssignmentRepository;
use App\Services\Zendesk\JobStatusCollection;
use App\Services\Assignments\PreparedAssignmentCollection;

class AssignmentService
{
    /** @var AssignmentRepository */
    protected $assignmentRepo;

    /** @var AgentRepository */
    protected $agentRepo;

    /** @var TicketService */
    protected $ticketService;

    /** @var RoundRobinEngine */
    protected $roundRobinEngine;

    public function __construct(AssignmentRepository $assignmentRepo, AgentRepository $agentRepo, TicketService $ticketService, RoundRobinEngine $roundRobinEngine)
    {
        $this->assignmentRepo = $assignmentRepo;
        $this->ticketService = $ticketService;
        $this->agentRepo = $agentRepo;
        $this->roundRobinEngine = $roundRobinEngine;
    }

    /**
     * Assign tiket bikin pendingan di DB 
     *
     * @param [type] $batch
     * @param Collection<Task> $tasks
     * @return JobStatusCollection
     */
    public function assignBatch($batch, Collection $tasks): JobStatusCollection
    {
        return $this->makeAssignments($batch, $tasks)
                    ->tap(function(PreparedAssignmentCollection $preparedAssignments) {
                        $this->assignmentRepo->createLogs($preparedAssignments);
                    })
                    ->pipe(function(PreparedAssignmentCollection $assignments) {
                        return $this->ticketService->assign($assignments);
                    });

    }

    /**
     * Unassign tiket bikin pendingan di DB  
     *
     * @param string $batch
     * @param AgentCollection $agents
     * @return JobStatusCollection
     */
    public function unassignBatch($batch, AgentCollection $agents): JobStatusCollection
    {
        return $this->makeUnassignments($batch, $agents)
                    ->tap(function(PreparedAssignmentCollection $preparedAssignments) {
                        $this->assignmentRepo->createLogs($preparedAssignments);
                    })
                    ->pipe(function(PreparedAssignmentCollection $assignments) {
                        return $this->ticketService->assign($assignments);
                    });
    }

    /**
     * reconcile log assignment berdasarkan batch
     *
     * @param [type] $batch
     * @param [type] $successTicketIds
     * @param [type] $failedTicketIds
     * @return void
     */
    public function updateLogs($batch, array $successTicketIds, array  $failedTicketIds)
    {
        $this->assignmentRepo->updateLogs($batch, $successTicketIds, $failedTicketIds);
    }

    /**
     * Undocumented function
     *
     * @param $batch
     * @param Collection<Task> $tasks
     * @return PreparedAssignmentCollection
     */
    public function makeAssignments($batch, Collection $tasks): PreparedAssignmentCollection
    {
        $slot = $this->roundRobinEngine->newSlot($batch, now());
        $tickets = $this->ticketService->assignableByViews($tasks);
        $agents = $this->agentRepo->getAssignable($tasks);

        $recentlyFailedAssignments = $this->assignmentRepo->getRecentlyFailedAssignments();
        $latestAssignmentsPerView = $this->assignmentRepo->getLatestAssignmentsPerView($agents);
        $agentOrders = $this->getAgentOrders($latestAssignmentsPerView, $agents, $tasks);
        
        $slot->addAssignableAgents($agents);
        $slot->setAgentOrders($agentOrders);
        $slot->setOrdersPerView($latestAssignmentsPerView);
        $slot->addRetriedAssignments($recentlyFailedAssignments);
        $slot->assignWithPriority($tickets);
        
        return $slot->makePreparedAssignments();
    }

    /**
     * Bikin unassignments berdasarkan agents
     *
     * @param string $batch
     * @param AgentCollection $agents
     * @return PreparedAssignmentCollection
     */
    public function makeUnassignments(string $batch, AgentCollection $agents): PreparedAssignmentCollection
    {
        $tickets = $this->ticketService->getAssignedByAgents($agents);

        $agentDictionary = $agents->groupById();
        $now = now();
        return (new PreparedAssignmentCollection($tickets->all()))->filterMap(function($ticket) use ($agentDictionary, $batch, $now) {
            $agent = $agentDictionary->getByTicket($ticket);

            return $agent ? (new PreparedAssignment($agent, $ticket, $ticket->viewId(), Assignment::TYPE_UNASSIGNMENT, $batch, $now)) : null;
        });
    }

    /**
     * Berformat collect([['viewId:1-groupId1' => collect([1,2, ...])], [...]])
     * 
     * @param AssignmentCollection $latestAssignmentsPerView
     * @param AgentCollection $agents
     * @param EloquentCollection $tasks
     * @return Collection
     */
    public function getAgentOrders(AssignmentCollection $latestAssignmentsPerView, AgentCollection $agents, Collection $tasks): Collection
    {
        $rules = DB::table('rules')->whereIn('task_id', $tasks->pluck('id')->all())->get();

        $tasksByAgents = $rules->groupBy('agent_id')->map(function($a) use ($tasks) { return $tasks->whereIn('id', $a->pluck('task_id'))->values();});

        $assignedViewIdsByAgent = $tasksByAgents->map(function($tasks, $agent) {
            return $tasks->pluck('zendesk_view_id');
        });

        $agentIdsByOrderTags = $agents->groupBy(function (Agent $agent) use ($assignedViewIdsByAgent) {
                                            return $this->getOrderTagsByAgent($agent, $assignedViewIdsByAgent)->all();
                                        })
                                        ->mapWithKeys(function($agents, $orderTag) {
                                            return [$orderTag => $agents->pluck('id')];
                                        });

        return collect($agentIdsByOrderTags->all());
    }

    public function getAgentOrderPerView(AgentCollection $agents, Collection $tasks)
    {
        
    }

        /**
     * @param Agent $agent
     * @param [type] $assignedViewIdsByAgent
     * @return Collection
     */
    public function getOrderTagsByAgent(Agent $agent, $assignedViewIdsByAgent): Collection
    {
        $agentViewIds = $assignedViewIdsByAgent->get($agent->id) ?: collect();
        return $agentViewIds->merge([Agent::DEFAULT_VIEW])
                            ->crossJoin(collect($agent->zendeskGroupId())
                            ->merge(Agent::DEFAULT_GROUP))
                            ->map(function($pair) {
                                return (new OrderTag($pair[0], $pair[1]))->__toString();
                            });
    }
}