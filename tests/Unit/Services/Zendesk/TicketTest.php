<?php

namespace Tests\Unit\Services\Zendesk;

use App\Collections\AgentCollection;
use Tests\TestCase;
use App\Traits\RoundRobinable;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Services\Zendesk\TicketCollection;
use App\Task;
use Tests\Helper\Seeder\AgentCollectionSeeder;
use Tests\Helper\Seeder\TicketCollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends TestCase
{
    public function __construct()
    {
        parent::__construct();
    }

    public function setUp() : void {
        parent::setUp();
        putenv("ZENDESK_AGENT_NAMES_FIELD=123456");
    }

    /**
     */
    public function test_getOrderIdentifierGetTheMostSpecificTagFirst() {

        $ticket = new Ticket((object)["id" => 1, "group_id" => 123456]);
        $ticket->task = factory(Task::class)->make(['zendesk_view_id' => 'view1']);
        $ticketNoView = new Ticket((object)["id" => 1, "group_id" => 123456]);
        $ticketNoGroup = new Ticket((object)["id" => 1, "group_id" => null]);
        $ticketNoGroup->view_id = $ticket->task = factory(Task::class)->make(['zendesk_view_id' => 'view1']);
        $ticketNoGroupNoView = new Ticket((object)["id" => 1, "group_id" => null]);

        $tag = $ticket->getOrderIdentifier();
        $NoViewTicketTag = $ticketNoView->getOrderIdentifier();
        $NoGroupTicketTag = $ticketNoGroup->getOrderIdentifier();
        $ticketNoGroupNoViewTag = $ticketNoGroupNoView->getOrderIdentifier();

        $this->assertSame($tag->viewId, "view1");
        $this->assertSame($tag->groupId, 123456);
    }

}