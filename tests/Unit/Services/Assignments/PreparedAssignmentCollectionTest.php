<?php

namespace Tests\Unit\Services\Assignments;

use App\Agent;
use App\Assignment;
use Tests\TestCase;
use App\Services\Zendesk\Ticket;
use App\Services\Assignments\PreparedAssignment;
use App\Services\Assignments\PreparedAssignmentCollection;


class PreparedAssignmentCollectionTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setUp() : void {
        parent::setUp();
        putenv("ZENDESK_AGENT_NAMES_FIELD=123456");
    }

    public function test_chunkUnassigmentsBody_membagi_berdasarkan_agentnya_dulu_lalu_di_chunk_100()
    {
        $unassignmentOne = new PreparedAssignment(factory(Agent::class)->make(['id' => 1]), new Ticket((object) ['id' => 123]), null, Assignment::TYPE_UNASSIGNMENT, "tes", now());
        $unassignmentTwo = new PreparedAssignment(factory(Agent::class)->make(['id' => 2]), new Ticket((object) ['id' => 123]), null, Assignment::TYPE_UNASSIGNMENT, "tes", now());
        $unassignmentThree = new PreparedAssignment(factory(Agent::class)->make(['id' => 2]), new Ticket((object) ['id' => 123]), null, Assignment::TYPE_UNASSIGNMENT, "tes", now());
        
        $preparedAssignments = new PreparedAssignmentCollection([$unassignmentOne, $unassignmentTwo, $unassignmentThree]);

        $bodies = $preparedAssignments->chunkUnassigmentsBody(function($body) {
            return 4;
        });
        dd($bodies);
    }
    
    
}
