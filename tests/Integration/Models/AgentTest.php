<?php

namespace Tests\Integration\Jobs;

use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use Illuminate\Support\Str;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_getUnassignableTickets_returns_assigned_tickets_that_have_not_been_updated()
    {
        $agent = factory(Agent::class)->create();
        $agentParams = [
            'agent_id' => $agent->id,
            'agent_name' => $agent->fullName
        ];

        $unassignedTicket = factory(Assignment::class)->create($agentParams);
        factory(Assignment::class, 3)->create($agentParams); //ticket num 2, 3, 4
        $solvedTicket = factory(Assignment::class)->create($agentParams);
        factory(Assignment::class)->state('unassignment')->create(array_merge($agentParams, ['zendesk_ticket_id' => $unassignedTicket->zendesk_ticket_id]));
        factory(Assignment::class)->state('already_solved')->create(array_merge($agentParams, ['zendesk_ticket_id' => $solvedTicket->zendesk_ticket_id]));

        $tickets = $agent->getUnassignedTickets();
        
        $this->assertCount(3, $tickets);
        $this->assertArraySubset([2, 3, 4], $tickets->pluck('id'), true);
     }

     public function test_getIdentifierTags()
     {
        /** @var \App\Agent&\PHPUnit\Framework\MockObject\MockObject $agent */
        $agent = $this->getMockBuilder(Agent::class)
              ->setMethods(['assignedViewIds', 'zendeskGroupId'])
              ->getMock();

        $agent->method('assignedViewIds')->willReturn(["view1", "view2"]);
        $agent->method('zendeskGroupId')->willReturn("123456");
        
        $tags = $agent->getOrderIdentifierTags()->map->__toString()->all();
        
        $this->assertCount(6, $tags);        
     }

     public function test_latestAssignmentOrder_akanMendapatkanIdDariAssignmentTerakhirPerView()
     {
        $agent = factory(Agent::class)->make();
        $agentTwo = factory(Agent::class)->make(['id' => 2]);
        $assignmentOne = factory(Assignment::class)->make([
            'id' => 1,
            'agent_id' => $agent->id,
            'zendesk_view_id' => "view1"
        ]);
        $assignmentTwo = factory(Assignment::class)->make([
            'id' => 2,
            'agent_id' => $agent->id,
            'zendesk_view_id' => "view1"
        ]);
        $assignmentThree = factory(Assignment::class)->make([
            'id' => 3,
            'agent_id' => $agent->id,
            'zendesk_view_id' => "view2"
        ]);
        $assignments = (new Assignment())->newCollection([$assignmentOne, $assignmentTwo, $assignmentThree]);
        $agent->latestAssignmentsByViewId = $assignments->mapWithKeys(function($assignment) {
            return [$assignment->zendesk_view_id => $assignment];
        });

        // dd($agentTwo->latest);
        $this->assertSame(2, $agent->latestAssignmentOrder("view:view1-group:x"));
        $this->assertNull($agentTwo->latestAssignmentOrder("view:view1-group:x"));
        $this->assertSame(3, $agent->latestAssignmentOrder("view:view2-group:x"));
        $this->assertNull($agent->latestAssignmentOrder("viewNotExist"));
        $this->assertNull($agent->latestAssignmentOrder(null));       
     }
}