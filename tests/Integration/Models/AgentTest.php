<?php

namespace Tests\Integration\Jobs;

use Database\Factories\AgentFactory;
use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use Illuminate\Support\Str;
use App\Services\ZendeskService;
use Database\Factories\AssignmentFactory;
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
        $agent = AgentFactory::new()->create();
        $agentParams = [
            'agent_id' => $agent->id,
            'agent_name' => $agent->fullName
        ];

        $unassignedTicket = AssignmentFactory::new()->create($agentParams);
        AssignmentFactory::new()->count(3)->create($agentParams); //ticket num 2, 3, 4
        $solvedTicket = AssignmentFactory::new()->create($agentParams);
        AssignmentFactory::new()->unassignment()->create(array_merge($agentParams, ['zendesk_ticket_id' => $unassignedTicket->zendesk_ticket_id]));
        AssignmentFactory::new()->already_solved()->create(array_merge($agentParams, ['zendesk_ticket_id' => $solvedTicket->zendesk_ticket_id]));

        $tickets = $agent->getUnassignedTickets();
        
        $this->assertCount(3, $tickets);
        $this->assertArraySubset([2, 3, 4], $tickets->pluck('id'), true);
     }
}