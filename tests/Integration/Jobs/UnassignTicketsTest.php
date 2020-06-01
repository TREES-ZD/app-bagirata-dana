<?php

namespace Tests\Integration\Jobs;

use Mockery;
use App\Agent;
use App\Assignment;
use Tests\TestCase;
use App\AvailabilityLog;
use App\Jobs\UnassignTickets;
use App\Services\ZendeskService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnassignTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->job = null;
    }

    public function test_tickets_get_unassigned()
    {
        factory(Assignment::class)->create([
            "type" => Agent::ASSIGNMENT,
            "agent_id" => 100,
            'zendesk_ticket_id' => 1,
            'zendesk_view_id' => 123
        ]);
        factory(AvailabilityLog::class)->create([
            "status" => AvailabilityLog::AVAILABLE,
            "agent_id" => 100
        ]);
        $agent = factory(Agent::class)->create([
            "id" => 100
        ]);

        $zendesk = Mockery::mock(ZendeskService::class);
        $zendesk->shouldReceive('getTicketsByIds')
                ->andReturn(collect([
                    (object) ['id' => 1, "subject" => "subject_a", "tags" => ["a"]],
                ]));
        $zendesk->shouldReceive('updateTicket')
                ->once();

        $this->job = new UnassignTickets($agent);
        $this->job->handle($zendesk);

        $this->assertDatabaseHas('assignments', [
            'type' => Agent::UNASSIGNMENT,
            'agent_id' => 100
        ]);
    }    
}
