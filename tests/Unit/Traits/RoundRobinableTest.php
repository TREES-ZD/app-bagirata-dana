<?php

namespace Tests\Unit;

use App\Collections\AgentCollection;
use Tests\TestCase;
use App\Traits\RoundRobinable;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Services\Zendesk\TicketCollection;
use Tests\Helper\Seeder\AgentCollectionSeeder;
use Tests\Helper\Seeder\TicketCollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoundRobinableTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();

        $this->rr = new class { use RoundRobinable; };
    }

    /**
     * @group task
     * @group roundRobin
     */

     
    public function test_tickets_with_group_id_assigned_to_agents_on_the_same_group() {
        $agentAndi = factory(\App\Agent::class)
                    // ->state('unavailable')
                    ->make([
                        "id" => 1,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => 11,
                        "zendesk_group_name" => "GroupA",
                        "zendesk_custom_field_id" => "andi",
                        "zendesk_custom_field_name" => "Andi"
                    ]);
        $agentBudi = factory(\App\Agent::class)
                    ->make([
                        "id" => 2,
                        "zendesk_agent_id" => 999,
                        "zendesk_agent_name" => "LICENSEDAGENT",
                        "zendesk_group_id" => 2233,
                        "zendesk_group_name" => "GroupBC",
                        "zendesk_custom_field_id" => "budi",
                        "zendesk_custom_field_name" => "Budi"
                    ]);
        $agentCharlie = factory(\App\Agent::class)
                        ->make([
                            "id" => 3,
                            "zendesk_agent_id" => 999,
                            "zendesk_agent_name" => "LICENSEDAGENT",
                            "zendesk_group_id" => 2233,
                            "zendesk_group_name" => "GroupBC",
                            "zendesk_custom_field_id" => "charlie",
                            "zendesk_custom_field_name" => "Charlie"
                        ]);
        $agents = new AgentCollection([$agentAndi, $agentBudi, $agentCharlie]);
        
        putenv("ZENDESK_AGENT_NAMES_FIELD=123456");
        $tickets = new TicketCollection([
            $ticketOne = new Ticket((object) ["id" => 1, "subject" => "tiket 1", "status" => "open", "assignee_id" => null, "group_id" => 11, "custom_fields" => [
                (object) ["id" => 123456, "value" => null]
            ]]),
            $ticketTwo = new Ticket((object) ["id" => 2, "subject" => "tiket 2", "status" => "open", "assignee_id" => 999, "group_id" => 2233, "custom_fields" => [
                (object) ["id" => 123456, "value" => "budi"]
            ]]),
            $ticketThree = new Ticket((object) ["id" => 3, "subject" => "tiket 3", "status" => "open", "assignee_id" => 999, "group_id" => 2233, "custom_fields" => [
                (object) ["id" => 123456, "value" => "charlie"]
            ]]),
            $ticketFour = new Ticket((object) ["id" => 4, "subject" => "tiket 4", "status" => "open", "assignee_id" => 999, "group_id" => 2233, "custom_fields" => [
                (object) ["id" => 123456, "value" => "budi"]
            ]]),
            $ticketFive = new Ticket((object) ["id" => 5, "subject" => "tiket 5", "status" => "open", "assignee_id" => 999, "group_id" => 2233, "custom_fields" => [
                (object) ["id" => 123456, "value" => "charlie"]
            ]])
        ]);

        $assignments = $this->rr->createTicketAssignments($agents, $tickets, "somerandomuuid");
        dd($assignments);

        $this->assertCount(0, $assignments);
        
    }

    public function test_group_yang_assigneenya_kosong_ngga_ketimpa_dengan_group_lain() {

    }

    public function test_semua_keassign() {
        
    }

    /**
     * @group roundRobin
     */
    public function test_tickets_are_equally_divided() {
        $this->markTestIncomplete();
        return;
        $agents = new Collection([
            (object) ["zendesk_assignee_name" => "andi", "zendesk_group_id" => "111", "zendesk_agent_id" => "10"],
            (object) ["zendesk_assignee_name" => "budi", "zendesk_group_id" => "222", "zendesk_agent_id" => "11"],
            (object) ["zendesk_assignee_name" => "charlie", "zendesk_group_id" => "333", "zendesk_agent_id" => "12"]
        ]);
        $tickets = new Collection([
            (object) ["title" => "a", "group_id" => null, "assignee_id" => null],
            (object) ["title" => "b", "group_id" => null, "assignee_id" => null],
            (object) ["title" => "c", "group_id" => null, "assignee_id" => null],
            (object) ["title" => "d", "group_id" => null, "assignee_id" => null],
            (object) ["title" => "e", "group_id" => null, "assignee_id" => null],
            (object) ["title" => "f", "group_id" => null, "assignee_id" => null],                        
        ]);

        $assignments = $this->rr->createTicketAssignments($agents, $tickets, "batchuuid");


        $this->assertCount(6, $assignments);
        $this->assertEquals("andi", $assignments[0]->get('agent')->zendesk_assignee_name);
        $this->assertEquals("budi", $assignments[1]->get('agent')->zendesk_assignee_name);        
        $this->assertEquals("charlie", $assignments[2]->get('agent')->zendesk_assignee_name);        
        $this->assertEquals("andi", $assignments[3]->get('agent')->zendesk_assignee_name);
        $this->assertEquals("budi", $assignments[4]->get('agent')->zendesk_assignee_name);        
        $this->assertEquals("charlie", $assignments[5]->get('agent')->zendesk_assignee_name);
        $this->assertEquals("a", $assignments[0]->get('ticket')->title);
        $this->assertEquals("b", $assignments[1]->get('ticket')->title);        
        $this->assertEquals("c", $assignments[2]->get('ticket')->title);        
        $this->assertEquals("d", $assignments[3]->get('ticket')->title);
        $this->assertEquals("e", $assignments[4]->get('ticket')->title);        
        $this->assertEquals("f", $assignments[5]->get('ticket')->title);                        
    }    

    /**
     * @group task
     * @group roundRobin
     */
    public function test_no_assignee_if_agent_not_in_tickets_group() {
        $this->markTestIncomplete();
        return;    
        $agents = new Collection([
            (object) ["zendesk_assignee_name" => "andi", "zendesk_group_id" => "111"],
            (object) ["zendesk_assignee_name" => "budi", "zendesk_group_id" => "222"],
            (object) ["zendesk_assignee_name" => "charlie", "zendesk_group_id" => "333"]
        ]);
        $tickets = new Collection([
            (object) ["title" => "a", "group_id" => "999", "assignee_id" => null],
            (object) ["title" => "b", "group_id" => "999", "assignee_id" => null]
        ]);  
        $assignments = $this->rr->createAssignments($agents, $tickets, "batchuuid");
        
        $this->assertCount(0, $assignments);
    }

    /**
     * @group task
     * @group roundRobin
     */
    public function test_reassigned_tickets_are_grouped_first_then_divided_equally() {
        $this->markTestIncomplete();
        return;    
        $agents = new Collection([
            (object) ["zendesk_assignee_name" => "andi", "zendesk_group_id" => "111"],
            (object) ["zendesk_assignee_name" => "budi", "zendesk_group_id" => "222"],
            (object) ["zendesk_assignee_name" => "charlie", "zendesk_group_id" => "333"]
        ]);
        $tickets = new Collection([
            (object) ["title" => "NOT_ASSIGNED_TO_ANYONE", "group_id" => null],
            (object) ["title" => "ASSIGNED_NULL_TO_BUDI", "group_id" => null],
            (object) ["title" => "", "group_id" => null],
            (object) ["title" => "ASSIGNED_TO_CHARLIE_SECOND", "group_id" => "333"],
            (object) ["title" => "to_andi_again", "group_id" => null],
            (object) ["title" => "ASSIGNED_TO_ANDI_FIRST", "group_id" => "111"],
        ]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets, "batchuuid");
        // $this->assertCount(4, $assignments);
        // $this->assertEquals("andi", $assignments[0]->get('agent')->zendesk_assignee_name);
        // $this->assertEquals("budi", $assignments[1]->get('agent')->zendesk_assignee_name);        
        // $this->assertEquals("charlie", $assignments[2]->get('agent')->zendesk_assignee_name);        
        // $this->assertEquals("andi", $assignments[3]->get('agent')->zendesk_assignee_name);
        // $this->assertEquals("budi", $assignments[4]->get('agent')->zendesk_assignee_name);        
        // $this->assertNull($assignments[5]);
        // $this->assertEquals("a", $assignments[0]->get('ticket')->title);
        // $this->assertEquals("b", $assignments[1]->get('ticket')->title);        
        // $this->assertEquals("c", $assignments[2]->get('ticket')->title);        
        // $this->assertEquals("d", $assignments[3]->get('ticket')->title);
        // $this->assertEquals("e", $assignments[4]->get('ticket')->title);        
        // $this->assertEquals("f", $assignments[5]->get('ticket')->title);         
    }    
}