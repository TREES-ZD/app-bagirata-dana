<?php

namespace Tests\Unit;

use App\Traits\RoundRobinable;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

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
        $agents = new Collection([
            (object) ["zendesk_assignee_name" => "andi", "zendesk_group_id" => "111"],
            (object) ["zendesk_assignee_name" => "budi", "zendesk_group_id" => "222"],
            (object) ["zendesk_assignee_name" => "charlie", "zendesk_group_id" => "333"]
        ]);
        $tickets = new Collection([
            (object) ["title" => "a", "group_id" => "222", "assignee_id" => null],
            (object) ["title" => "b", "group_id" => "222", "assignee_id" => null]
        ]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets);
        
        $this->assertCount(2, $assignments);
        $this->assertIsObject($assignments[0]->get('agent'));
        $this->assertIsObject($assignments[0]->get('ticket'));
        $this->assertIsObject($assignments[1]->get('agent'));
        $this->assertIsObject($assignments[1]->get('ticket'));
        $this->assertNull($assignments->get(2));        
        $this->assertEquals("budi", $assignments[0]->get('agent')->zendesk_assignee_name);
        $this->assertEquals("a", $assignments[0]->get('ticket')->title);
        $this->assertEquals("budi", $assignments[1]->get('agent')->zendesk_assignee_name);
        $this->assertEquals("b", $assignments[1]->get('ticket')->title);

    }

    /**
     * @group roundRobin
     */
    public function test_tickets_are_equally_divided() {
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

        $assignments = $this->rr->createAssignments($agents, $tickets);
        
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
        $agents = new Collection([
            (object) ["zendesk_assignee_name" => "andi", "zendesk_group_id" => "111"],
            (object) ["zendesk_assignee_name" => "budi", "zendesk_group_id" => "222"],
            (object) ["zendesk_assignee_name" => "charlie", "zendesk_group_id" => "333"]
        ]);
        $tickets = new Collection([
            (object) ["title" => "a", "group_id" => "999", "assignee_id" => null],
            (object) ["title" => "b", "group_id" => "999", "assignee_id" => null]
        ]);
        
        $assignments = $this->rr->createAssignments($agents, $tickets);
        
        $this->assertCount(0, $assignments);
    }

    /**
     * @group task
     * @group roundRobin
     */
    public function test_reassigned_tickets_are_grouped_first_then_divided_equally() {
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
        
        $assignments = $this->rr->createAssignments($agents, $tickets);
        $this->markTestIncomplete();
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