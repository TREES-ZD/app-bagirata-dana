<?php

namespace Tests\Integration\Jobs;

use App\Task;
use App\Agent;
use App\Assignment;
use Tests\TestCase;
use Tests\Helper\TicketFactory;
use Tests\Helper\ZendeskFactory;
use Illuminate\Support\Facades\DB;
use App\Jobs\Assignments\AssignBatch;
use Tests\Helper\ZendeskWrapperFixture;
use App\Services\Zendesk\ZendeskWrapper;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AssignBatchTest extends TestCase
{
    use RefreshDatabase;

    use DispatchesJobs;

    use ZendeskWrapperFixture;

    const GROUPA_ID = 11;
    const GROUPAD_ID = 1144;
    const GROUPBC_ID = 2233;

    public function setUp(): void
    {
        parent::setUp();

        putenv('ZENDESK_AGENT_NAMES_FIELD=123456');

        $this->mockZendeskWrapper();
    }
    
    /**
     * hanya mock panggilan ke API zendesk, selebihnya real
     *
     * @return void
     */
    public function test_MasukKeDatabase()
    {
        $taskOne = factory(Task::class)->create(["zendesk_view_id" => "250", 'zendesk_view_position' => 1]);
        $agentOne = factory(Agent::class)->states(['agentOne', "groupOne"])->create();
        $agentTwo = factory(Agent::class)->states(['agentTwo', 'groupTwo'])->create();
        $taskOne->rules()->attach([$agentOne->id, $agentTwo->id], ['priority' => 1]);
        $tasks = (new Task())->newCollection([$taskOne]);
        
        $this->dispatchNow(new AssignBatch($tasks));

        $this->assertSame(250, Assignment::where('response_status', Assignment::RESPONSE_STATUS_SUCCESS)->where('type', Assignment::TYPE_ASSIGNMENT)->count());
    }

    public function test_retriedAssignmentTidakDiperhitungkanKetikaPembagianOrderPadaAssignmentSelanjutnya()
    {
        $agentOne = factory(Agent::class)->states(['agentOne', "groupOne"])->create();
        $agentTwo = factory(Agent::class)->states(['agentTwo', 'groupTwo'])->create();
        $agentThree = factory(Agent::class)->states(['agentThree', 'groupThree'])->create();
        $taskOne = factory(Task::class)->create(["zendesk_view_id" => self::$viewTICKETS567, 'zendesk_view_position' => 1]); // #5, #6, #7
        $taskOne->rules()->attach([$agentOne->id, $agentTwo->id, $agentThree->id], ['priority' => 1]);
        factory(Assignment::class)->createMany([
            [
                "agent_id" => $agentThree->id,
                "agent_name" => $agentThree->fullName,
                "batch_id" => "batchid100",
                "zendesk_view_id" => $taskOne->zendesk_view_id,
                "zendesk_ticket_id" => 6,
                "response_status" => "FAILED"
            ],
            [
                "agent_id" => $agentOne->id,
                "agent_name" => $agentOne->fullName,
                "batch_id" => "batchid100",
                "zendesk_view_id" => $taskOne->zendesk_view_id,
                "zendesk_ticket_id" => 7
            ],
            [
                "agent_id" => $agentTwo->id,
                "agent_name" => $agentTwo->fullName,
                "batch_id" => "batchid100",
                "zendesk_view_id" => $taskOne->zendesk_view_id,
                "zendesk_ticket_id" => 8
            ],
            [
                "agent_id" => $agentThree->id,
                "agent_name" => $agentThree->fullName,
                "batch_id" => "batchid200",
                "type" => Assignment::TYPE_RETRIED_ASSIGNMENT,
                "zendesk_view_id" => $taskOne->zendesk_view_id,
                "zendesk_ticket_id" => 6,
            ]
        ]);

        $this->dispatchNow(new AssignBatch((new Task())->newCollection([$taskOne])));

        //id, agent_id, zendesk_ticket_id
        $expectedResults = [
            [5, $agentThree->id, 5],
            [6, $agentOne->id, 6],
            [7, $agentTwo->id, 7]
        ];
        foreach ($expectedResults as $result) {
            $this->assertDatabaseHas('assignments', [
                "id" => $result[0],
                "agent_id" => $result[1],
                "zendesk_ticket_id" => $result[2]
            ]);
                
        }

        // dd(DB::table('assignments')->get());
    }

    public function test_tiketHanyaDibatasi500SajaPerViewnya()
    {
        // Arrange
        $task = factory(Task::class)->create(["zendesk_view_id" => self::$viewWith501AssignableTickets]);
        $agents = factory(Agent::class, 5)->create(['zendesk_group_id' => 1]);
        $agents->each(function($agent) use ($task) {
            $task->rules()->attach($agent->id, ['priority' => 1]);
        });

        // Act
        $this->dispatchNow(new AssignBatch((new Task)->newCollection([$task])));
        
        // Assert
        $this->assertCount(100, DB::table('assignments')->where('agent_id', 1)->where('response_status', '200')->get(["id"]), "only 500 ticket is assigned per view per batch");
        $this->assertCount(100, DB::table('assignments')->where('agent_id', 2)->where('response_status', '200')->get(["id"]));
        $this->assertCount(100, DB::table('assignments')->where('agent_id', 3)->where('response_status', '200')->get(["id"]));
        $this->assertCount(100, DB::table('assignments')->where('agent_id', 4)->where('response_status', '200')->get(["id"]));
        $this->assertCount(100, DB::table('assignments')->where('agent_id', 5)->where('response_status', '200')->get(["id"]));
    }

    public function test_adaAgentYangDapetTiketLebihPada101Tiket()
    {
        // Arrange
        $task = factory(Task::class)->create(["zendesk_view_id" => self::$view101]);
        $agents = factory(Agent::class, 5)->create();
        $agents->each(function($agent) use ($task) {
            $task->rules()->attach($agent->id, ['priority' => 1]);
        });

        $this->dispatchNow(new AssignBatch((new Task)->newCollection([$task])));
        
        // Assert
        $this->assertCount(21, DB::table('assignments')->where('agent_id', 1)->where('response_status', '200')->get(["id"]));
        $this->assertCount(20, DB::table('assignments')->where('agent_id', 2)->where('response_status', '200')->get(["id"]));
        $this->assertCount(20, DB::table('assignments')->where('agent_id', 3)->where('response_status', '200')->get(["id"]));
        $this->assertCount(20, DB::table('assignments')->where('agent_id', 4)->where('response_status', '200')->get(["id"]));
        $this->assertCount(20, DB::table('assignments')->where('agent_id', 5)->where('response_status', '200')->get(["id"]));
    }

    public function test_ticketsAkanDimergeAntarViewBaruDibagiRata()
    {
        // Arrange
        $taskOne = factory(Task::class)->create(["zendesk_view_id" => self::$viewTICKETS1001TO1004, 'zendesk_view_position' => 1]); //tiket 1001,1002,1003,1004
        $taskTwo = factory(Task::class)->create(["zendesk_view_id" => "100302", 'zendesk_view_position' => 2]); //tiket 1003,1004,1005
        list($agentOne, $agentTwo, $agentThree) = factory(Agent::class, 3)->create();
        $taskOne->rules()->attach([
            $agentOne->id => ['priority' => 1],
            $agentTwo->id => ['priority' => 1],
        ]);
        $taskTwo->rules()->attach([
            $agentThree->id => ['priority' => 1]
        ]);

        $this->dispatchNow(new AssignBatch((new Task)->newCollection([$taskOne, $taskTwo])));

        // Assert
        // dd(DB::table('assignments')->get());
        $this->assertSame(5, Assignment::count());
        $this->assertDatabaseHas('assignments', ["id" => 1,"agent_id" => 1,"zendesk_ticket_id" => 1001]);
        $this->assertDatabaseHas('assignments', ["id" => 2,"agent_id" => 2,"zendesk_ticket_id" => 1002]);
        $this->assertDatabaseHas('assignments', ["id" => 3,"agent_id" => 1,"zendesk_ticket_id" => 1003]);
        $this->assertDatabaseHas('assignments', ["id" => 4,"agent_id" => 2,"zendesk_ticket_id" => 1004]);
        $this->assertDatabaseHas('assignments', ["id" => 5,"agent_id" => 3,"zendesk_ticket_id" => 1005]);
    }

    // public function test_cekPreviousAssignmentsSebelumPembagian()
    // {

    // }

    public function test_ticketAkanDigroupDuluSebelumDiassign()
    {
        $agents = factory(Agent::class)->createMany([
            [
                "id" => 1,
                "zendesk_agent_id" => 999,
                "zendesk_agent_name" => "LICENSEDAGENT",
                "zendesk_group_id" => self::GROUPAD_ID,
                "zendesk_group_name" => "GroupAD",
                "zendesk_custom_field_id" => "andi",
                "zendesk_custom_field_name" => "Andi"
            ],
            [
                "id" => 2,
                "zendesk_agent_id" => 999,
                "zendesk_agent_name" => "LICENSEDAGENT",
                "zendesk_group_id" => self::GROUPBC_ID,
                "zendesk_group_name" => "GroupBC",
                "zendesk_custom_field_id" => "budi",
                "zendesk_custom_field_name" => "Budi"
            ],
            [
                "id" => 3,
                "zendesk_agent_id" => 999,
                "zendesk_agent_name" => "LICENSEDAGENT",
                "zendesk_group_id" => self::GROUPBC_ID,
                "zendesk_group_name" => "GroupBC",
                "zendesk_custom_field_id" => "charlie",
                "zendesk_custom_field_name" => "Charlie"
            ],
            [
                "id" => 4,
                "zendesk_agent_id" => 999,
                "zendesk_agent_name" => "LICENSEDAGENT",
                "zendesk_group_id" => self::GROUPAD_ID,
                "zendesk_group_name" => "GroupAD",
                "zendesk_custom_field_id" => "doni",
                "zendesk_custom_field_name" => "Doni"
            ]
        ]);
        
        $tickets = new TicketCollection([
            app(TicketFactory::class)->id(1)->unassigned(self::GROUPAD_ID)->make(),
            app(TicketFactory::class)->id(2)->unassigned(self::GROUPAD_ID)->make(),
            app(TicketFactory::class)->id(3)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(4)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(5)->unassigned(self::GROUPBC_ID)->make(),
            app(TicketFactory::class)->id(6)->unassigned(self::GROUPBC_ID)->make(),
        ]);
        $taskOne = factory(Task::class)->create(['zendesk_view_id' => self::$viewUnassignedOneToSix]);
     
        //Act
        $this->dispatchNow(new AssignBatch((new Task)->newCollection([$taskOne])));
        
        //Assert
        $this->assertCount(6, DB::table('assignments')->count());
        $this->assertDatabaseHas('assignments', ["id" => 1,"agent_id" => 1]);
        $this->assertDatabaseHas('assignments', ["id" => 2,"agent_id" => 4]); //salah
        $this->assertDatabaseHas('assignments', ["id" => 3,"agent_id" => 2]);
        $this->assertDatabaseHas('assignments', ["id" => 4,"agent_id" => 3]);
        $this->assertDatabaseHas('assignments', ["id" => 5,"agent_id" => 2]);
        $this->assertDatabaseHas('assignments', ["id" => 6,"agent_id" => 3]);
    }
}
