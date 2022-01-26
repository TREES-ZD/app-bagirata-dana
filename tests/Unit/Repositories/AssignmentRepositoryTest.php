<?php

namespace Tests\Unit\Repositories;

use Mockery;
use App\Task;
use App\Agent;
use Tests\TestCase;
use Faker\Generator as Faker;
use App\Services\Zendesk\Ticket;
use App\Collections\AgentCollection;
use App\Repositories\AgentRepository;
use App\Repositories\TicketRepository;
use Tests\Helper\TicketFactory;
use App\Repositories\AssignmentRepository;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Database\Eloquent\Collection;
use App\Services\Assignments\RoundRobinEngine;
use Zendesk\API\Resources\Core\TicketComments;
use Illuminate\Foundation\Testing\RefreshDatabase;


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

    public function setUp() : void {
        parent::setUp();
        putenv("ZENDESK_AGENT_NAMES_FIELD=123456");
    }
    
    /**
     * @group migratedTest
     * 
     */
    public function test_makeAssignment_tidakAkanMengassignTicketYangSudahTerassign() {
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPA_ID,
                        "zendesk_group_name" => "GroupA",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => self::GROUPBC_ID,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie]);
        $tickets = new TicketCollection([
            app(TicketFactory::class)->id(1)->unassigned(self::GROUPA_ID)->make(),
            app(TicketFactory::class)->id(2)->assignedTo(self::GROUPBC_ID, 999, "budi")->make(),
            app(TicketFactory::class)->id(3)->assignedTo(self::GROUPBC_ID, 999, "charlie")->make(),
            app(TicketFactory::class)->id(4)->assignedTo(self::GROUPBC_ID, 999, "budi")->make(),
            app(TicketFactory::class)->id(5)->assignedTo(self::GROUPBC_ID, 999, "charlie")->make()    
        ]);
        
        $ticketRepoMock = $this->createMock(TicketRepository::class);
        $agentRepoMock = $this->createMock(AgentRepository::class);
        $roundRobinEngineMock = app(RoundRobinEngine::class);
        $tasksMock = $this->createMock(Collection::class);

        $tasksMock->method('pluck')->with($this->equalTo("zendesk_view_id"))->willReturn(collect());
        $ticketRepoMock->method('getAssignableByViews')->with($this->anything())->willReturn($tickets);
        $agentRepoMock->method('getAssignable')->with($this->anything())->willReturn($agents);
        $assignmentRepository = new AssignmentRepository($ticketRepoMock, $agentRepoMock, $roundRobinEngineMock);

        // Act
        $assignments = $assignmentRepository->makeAssignments("somerandomuuid", $tasksMock);

        // Assert
        $this->assertCount(1, $assignments);
    }
    
    /**
     * @group migratedTest
     */
    public function test_tickets_akan_digroup_dulu_baru_dibagi_rata() {
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => self::GROUPBC_ID,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agentDoni = factory(\App\Agent::class)
                    ->make([
                        "id" => 4,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "doni",
                        "zendesk_custom_field_name" => "Doni"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie, $agentDoni]);
        
        $tickets = new TicketCollection([
            app(TicketFactory::class)->id(1)->unassigned(self::GROUPAD_ID)->make(),
            app(TicketFactory::class)->id(2)->unassigned(self::GROUPAD_ID)->make(),
            app(TicketFactory::class)->id(3)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(4)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(5)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(6)->unassigned(self::GROUPBC_ID)->make(),
        ]);
        $ticketRepoMock = $this->createMock(TicketRepository::class);
        $agentRepoMock = $this->createMock(AgentRepository::class);
        $roundRobinEngine = app(RoundRobinEngine::class);
        $tasksMock = $this->createMock(Collection::class);

        $tasksMock->method('pluck')->with($this->equalTo("zendesk_view_id"))->willReturn(collect());
        $ticketRepoMock->method('getAssignableByViews')->with($this->anything())->willReturn($tickets);
        $agentRepoMock->method('getAssignable')->with($this->anything())->willReturn($agents);
        $assignmentRepository = new AssignmentRepository($ticketRepoMock, $agentRepoMock, $roundRobinEngine);

        //Act
        $assignments = $assignmentRepository->makeAssignments("somerandomuuid", $tasksMock);
        
        //Assert
        $this->assertCount(6, $assignments);
        $this->assertEquals($agentAndi->id, $assignments->get(0)->agent_id);
        $this->assertEquals($agentDoni->id, $assignments->get(1)->agent_id);
        $this->assertEquals($agentBudi->id, $assignments->get(2)->agent_id); //salah
        $this->assertEquals($agentCharlie->id, $assignments->get(3)->agent_id);
        $this->assertEquals($agentBudi->id, $assignments->get(4)->agent_id);
        $this->assertEquals($agentCharlie->id, $assignments->get(5)->agent_id);
    }

    /**
     * @group migratedTests
     */
    public function test_ticket_tanpa_assignee_dan_group_diassign_ke_siapa_aja_yang_available() {
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => self::GROUPBC_ID,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agentDoni = factory(\App\Agent::class)
                    ->make([
                        "id" => 4,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "doni",
                        "zendesk_custom_field_name" => "Doni"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie, $agentDoni]);
        $tickets = new TicketCollection([
            app(TicketFactory::class)->id(1)->unassigned()->make(),
            app(TicketFactory::class)->id(2)->unassigned()->make(),
            app(TicketFactory::class)->id(3)->unassigned()->make(),
            app(TicketFactory::class)->id(4)->unassigned()->make(),
            app(TicketFactory::class)->id(5)->unassigned()->make(),
            app(TicketFactory::class)->id(6)->unassigned()->make(),
        ]);
        
        $ticketRepoMock = $this->createMock(TicketRepository::class);
        $agentRepoMock = $this->createMock(AgentRepository::class);
        $roundRobinEngine = app(RoundRobinEngine::class);
        $tasksMock = $this->createMock(Collection::class);

        $tasksMock->method('pluck')->with($this->equalTo("zendesk_view_id"))->willReturn(collect());
        $ticketRepoMock->method('getAssignableByViews')->with($this->anything())->willReturn($tickets);
        $agentRepoMock->method('getAssignable')->with($this->anything())->willReturn($agents);
        $assignmentRepository = new AssignmentRepository($ticketRepoMock, $agentRepoMock, $roundRobinEngine);

        // Act
        $assignments = $assignmentRepository->makeAssignments("somerandomuuid", $tasksMock);

        // Assert
        $this->assertCount(6, $assignments);
        $this->assertEquals($agentAndi->id, $assignments->get(0)->agent_id);
        $this->assertEquals($agentBudi->id, $assignments->get(1)->agent_id);
        $this->assertEquals($agentCharlie->id, $assignments->get(2)->agent_id);
        $this->assertEquals($agentDoni->id, $assignments->get(3)->agent_id);
        $this->assertEquals($agentAndi->id, $assignments->get(4)->agent_id);
        $this->assertEquals($agentBudi->id, $assignments->get(5)->agent_id);
    }

    /**
     * @group migratedTests
     */
    public function test_ticket_unassign_yang_ada_groupnya_direserve_ke_agent_di_group_itu_sisanya_ke_agent_yang_available() {
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => self::GROUPBC_ID,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agentDoni = factory(\App\Agent::class)
                    ->make([
                        "id" => 4,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "doni",
                        "zendesk_custom_field_name" => "Doni"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie, $agentDoni]);
        
        $tickets = new TicketCollection([
            $ticketOne = new Ticket((object) ["id" => 1, "subject" => "tiket 1", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketTwo = new Ticket((object) ["id" => 2, "subject" => "tiket 2", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => self::GROUPAD_ID, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => self::GROUPBC_ID, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => null, "group_id" =>  self::GROUPAD_ID, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketSix = new Ticket((object) ["id" => 6, "subject" => "tiket 6", "status" => "open", "assignee_id" => null, "group_id" => self::GROUPBC_ID, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $ticketRepoMock = $this->createMock(TicketRepository::class);
        $agentRepoMock = $this->createMock(AgentRepository::class);
        $roundRobinEngine = app(RoundRobinEngine::class);
        $tasksMock = $this->createMock(Collection::class);

        $tasksMock->method('pluck')->with($this->equalTo("zendesk_view_id"))->willReturn(collect());
        $ticketRepoMock->method('getAssignableByViews')->with($this->anything())->willReturn($tickets);
        $agentRepoMock->method('getAssignable')->with($this->anything())->willReturn($agents);
        $assignmentRepository = new AssignmentRepository($ticketRepoMock, $agentRepoMock, $roundRobinEngine);
        
        // Act
        $assignments = $assignmentRepository->makeAssignments("somerandomuuid", $tasksMock);
        
        // Assert
        $this->assertCount(6, $assignments);
        $this->assertArraySubset([
            "agent_id" => $agentAndi->id,
            "ticket_id" => $ticketThree->id,
        ], (array) $assignments->get(0));
        $this->assertArraySubset([
            "agent_id" => $agentDoni->id,
            "ticket_id" => $ticketFive->id,
        ], (array) $assignments->get(1));
        $this->assertArraySubset([
            "agent_id" => $agentBudi->id,
            "ticket_id" => $ticketFour->id,
        ], (array) $assignments->get(2));
        $this->assertArraySubset([
            "agent_id" => $agentCharlie->id,
            "ticket_id" => $ticketSix->id,
        ], (array) $assignments->get(3));
        $this->assertArraySubset([
            "agent_id" => $agentAndi->id,
            "ticket_id" => $ticketOne->id,
        ], (array) $assignments->get(4));
        $this->assertArraySubset([
            "agent_id" => $agentBudi->id,
            "ticket_id" => $ticketTwo->id,
        ], (array) $assignments->get(5));
    }

    /**
     * @group migratedTests
     */
    public function test_ticket_terakhir_dari_assignee_per_group_beda_satu_detik() {
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => self::GROUPBC_ID,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agentDoni = factory(\App\Agent::class)
                    ->make([
                        "id" => 3,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "doni",
                        "zendesk_custom_field_name" => "Doni"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie, $agentDoni]);
        
        $tickets = new TicketCollection([
            $ticketOne = new Ticket((object) ["id" => 1, "subject" => "tiket 1", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketTwo = new Ticket((object) ["id" => 2, "subject" => "tiket 2", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => null, "group_id" =>  null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketSix = new Ticket((object) ["id" => 6, "subject" => "tiket 6", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketSeven = new Ticket((object) ["id" => 7, "subject" => "tiket 6", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $ticketRepoMock = $this->createMock(TicketRepository::class);
        $agentRepoMock = $this->createMock(AgentRepository::class);
        $roundRobinEngine = app(RoundRobinEngine::class);
        $tasksMock = $this->createMock(Collection::class);

        $tasksMock->method('pluck')->with($this->equalTo("zendesk_view_id"))->willReturn(collect());
        $ticketRepoMock->method('getAssignableByViews')->with($this->anything())->willReturn($tickets);
        $agentRepoMock->method('getAssignable')->with($this->anything())->willReturn($agents);
        $assignmentRepository = new AssignmentRepository($ticketRepoMock, $agentRepoMock, $roundRobinEngine);
        
        // Act
        $assignments = $assignmentRepository->makeAssignments("somerandomuuid", $tasksMock);
        $this->assertCount(7, $assignments);
        
        //Assert
        $this->markTestIncomplete();
        $this->assertEquals(0, $assignments->get(1)->created_at->diffInMinutes($assignments->get(0)->created_at));
        $this->assertEquals(1, $assignments->get(2)->created_at->diffInMinutes($assignments->get(1)->created_at));
        $this->assertEquals(0, $assignments->get(4)->created_at->diffInMinutes($assignments->get(3)->created_at));
        $this->assertEquals(1, $assignments->get(5)->created_at->diffInMinutes($assignments->get(4)->created_at));
    }

    /**
     * @group migratedTests
     */
    public function test_ticket_yang_terakhir_gagal_dapat_assignment_akan_diprioritaskan_ke_agent_yang_sama_di_assignment_sebelumnya() {
        $this->markTestIncomplete();
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agents = new AgentCollection([$agentBudi, $agentAndi]);
        
        $tickets = new TicketCollection([
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $assignmentThree = factory(\App\Assignment::class)
                        ->make([
                            'id' => 1,
                            'agent_id' => 1,
                            'agent_name' => 'LICENSEDAGENT (GroupAD, andi)',
                            'zendesk_ticket_id' => 3,
                            'zendesk_ticket_subject' => 'tiket 3',
                            'type' => 'ASSIGNMENT',
                            'response_status' => 'FAILED', 
                        ]);
        $failedAssignments = collect([$assignmentThree]);

        $assignments = $this->rr->createAssignments($agents, $tickets, "somerandomuuid", $failedAssignments);

        $this->markTestIncomplete();
        // $this->assertCount(2, $assignments);
        // $this->assertEquals(1, $assignments->get(0)->agent_id);
        // $this->assertEquals(3, $assignments->get(0)->ticket_id);
        // $this->assertEquals(2, $assignments->get(1)->agent_id);
        // $this->assertEquals(4, $assignments->get(1)->ticket_id);
    }

    /**
     * @group migratedTests
     */
    public function test_failed_assignments_yang_agentnya_sudah_tidak_ada_dihiraukan() {
        $this->markTestIncomplete();
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                    ->make([
                        "id" => 3,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "charlie",
                        "zendesk_custom_field_name" => "Charlie"
                    ]);
        $agents = new AgentCollection([$agentBudi, $agentCharlie]);
        
        $tickets = new TicketCollection([
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $assignmentThree = factory(\App\Assignment::class)
                        ->make([
                            'id' => 1,
                            'agent_id' => 1,
                            'agent_name' => 'LICENSEDAGENT (GroupAD, andi)',
                            'zendesk_ticket_id' => 3,
                            'zendesk_ticket_subject' => 'tiket 3',
                            'type' => 'ASSIGNMENT',
                            'response_status' => 'FAILED', 
                        ]);
        $failedAssignments = collect([$assignmentThree]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets, "somerandomuuid", $failedAssignments);

        $this->markTestIncomplete();
        // $this->assertCount(3, $assignments);
        // $this->assertNotEquals(1, $assignments->get(0)->agent_id);
        // $this->assertEquals(2, $assignments->get(0)->agent_id);
        // $this->assertEquals(3, $assignments->get(0)->ticket_id);
    }

    /**
     * @group migratedTests
     */
    public function test_assignment_yang_ngga_ada_ticketnya_dihiraukan() {
        $this->markTestIncomplete();
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                    ->make([
                        "id" => 3,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "charlie",
                        "zendesk_custom_field_name" => "Charlie"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie]);
        
        $tickets = new TicketCollection([
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $assignmentThree = factory(\App\Assignment::class)
                        ->make([
                            'id' => 1,
                            'agent_id' => 3,
                            'agent_name' => 'LICENSEDAGENT (GroupAD, andi)',
                            'zendesk_ticket_id' => 10,
                            'zendesk_ticket_subject' => 'tiket 10',
                            'type' => 'ASSIGNMENT',
                            'response_status' => 'FAILED', 
                        ]);
        $failedAssignments = collect([$assignmentThree]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets, "somerandomuuid", $failedAssignments);

        $this->markTestIncomplete();
        // $this->assertCount(3, $assignments);
    }

    /**
     * @group migratedTests
     */
    public function test_assignment_harus_unique_dan_ngambil_yang_pertama() {
        $this->markTestIncomplete();
        $agentAndi = factory(\App\Agent::class)
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPAD_ID,
                        "zendesk_group_name" => "GroupAD",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                    ->make([
                        "id" => 3,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => self::GROUPBC_ID,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "charlie",
                        "zendesk_custom_field_name" => "Charlie"
                    ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie]);
        
        $tickets = new TicketCollection([
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => null, "group_id" => null, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]])
        ]);

        $assignmentThree = factory(\App\Assignment::class)
                        ->make([
                            'id' => 1,
                            'agent_id' => 1,
                            'agent_name' => 'LICENSEDAGENT (GroupAD, andi)',
                            'zendesk_ticket_id' => 4,
                            'zendesk_ticket_subject' => 'tiket 4',
                            'type' => 'ASSIGNMENT',
                            'response_status' => 'FAILED', 
                        ]);
        $assignmentThreeSame = factory(\App\Assignment::class)
                        ->make([
                            'id' => 2,
                            'agent_id' => 2,
                            'agent_name' => 'LICENSEDAGENT (GroupAD, andi)',
                            'zendesk_ticket_id' => 4,
                            'zendesk_ticket_subject' => 'tiket 4',
                            'type' => 'ASSIGNMENT',
                            'response_status' => 'FAILED', 
                        ]);
        $failedAssignments = collect([$assignmentThree, $assignmentThreeSame]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets, "somerandomuuid", $failedAssignments);

        $this->markTestIncomplete();
        // $this->assertCount(3, $assignments);
        // $this->assertEquals(1, $assignments->get(0)->agent_id); // Tiket reserved
        // $this->assertEquals(4, $assignments->get(0)->ticket_id); // Tiket reserved 
    }
}
