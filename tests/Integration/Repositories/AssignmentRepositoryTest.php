<?php

namespace Tests\Unit\Repositories;

use Mockery;
use App\Task;
use App\Agent;
use App\Assignment;
use Tests\TestCase;
use Faker\Generator as Faker;
use Tests\Helper\TicketFactory;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Facades\DB;
use App\Collections\AgentCollection;
use App\Repositories\AgentRepository;
use App\Repositories\TicketRepository;
use App\Repositories\AssignmentRepository;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Assignments\RoundRobinEngine;
use Zendesk\API\Resources\Core\TicketComments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpParser\Node\Expr\Assign;

class AssignmentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    const GROUPA_ID = 11;
    const GROUPAD_ID = 1144;
    const GROUPBC_ID = 2233;

    
    public function __construct()
    {
        parent::__construct();
    }

    public function setUp(): void {
        parent::setUp();
    }
    
    public function test_getLatestAssignmentsPerView_akanReturnAssignmentTerakhirPerAgenPerView() 
    {
        /** @var AssignmentRepository $repo */
        $repo = app(AssignmentRepository::class);

        factory(Assignment::class)->createMany([
            ["id" => 1, "agent_id" => 1, "zendesk_view_id" => "111"],
            ["id" => 2, "agent_id" => 2, "zendesk_view_id" => "222"],
            ["id" => 3, "agent_id" => 1, "zendesk_view_id" => "222"],
            ["id" => 4, "agent_id" => 2, "zendesk_view_id" => "222"],
            ["id" => 5, "agent_id" => 1, "zendesk_view_id" => "111"],
            ["id" => 6, "agent_id" => 2, "zendesk_view_id" => "111"],
        ]);
        $agents = factory(Agent::class, 1)->create();

        $assignments = $repo->getLatestAssignmentsPerView($agents);
        
        // Assert
        dd($assignments);
        $this->markTestIncomplete("seharusnya ngga ada masalh");
    }
}
