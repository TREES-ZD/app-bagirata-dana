<?php

namespace Tests\Unit\Services\Assignments;

use App\Agent;
use App\Assignment;
use App\Collections\AgentCollection;
use App\Collections\AssignmentCollection;
use App\Services\Assignments\OrderTag;
use Tests\TestCase;
use App\Services\Assignments\RoundRobinEngine;
use App\Services\Zendesk\Ticket;
use Tests\Helper\TicketFactory;

class RoundRobinEngineTest extends TestCase
{
    public function setUp() : void {
        parent::setUp();
    }
    
    public function test_setOrdersPerView_akanMereorderAgentOrderBerdasarkanOrderPerAssignmentPerView()
    {
        $assignments = AssignmentCollection::make([
            factory(Assignment::class)->make(['id' => 1, 'agent_id' => 20, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 2, 'agent_id' => 30, 'zendesk_view_id' => 'view2']),
            factory(Assignment::class)->make(['id' => 3, 'agent_id' => 10, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 4, 'agent_id' => 30, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 5, 'agent_id' => 10, 'zendesk_view_id' => 'view2']),
            factory(Assignment::class)->make(['id' => 6, 'agent_id' => 20, 'zendesk_view_id' => 'view2'])
        ]);
        $rre = new RoundRobinEngine();
        $rre->agentOrders = collect([
            $tagViewOne = (string) (new OrderTag("view1", "group1")) => collect([10, 20, 30]),
            $tagViewTwo = (string) (new OrderTag("view2", "group2")) => collect([10, 20, 30])
        ]);

        $rre->setOrdersPerView($assignments);

        $this->assertSame([20, 10, 30], $rre->agentOrders[$tagViewOne]->all());
        $this->assertSame([30, 10, 20], $rre->agentOrders[$tagViewTwo]->all());
    }

    public function test_setOrdersPerView_akanMemprioritaskanDanMembiarkanOrderAgentIdJikaTidakAdaDiAssignment()
    {
        $assignments = AssignmentCollection::make([
            factory(Assignment::class)->make(['id' => 1, 'agent_id' => 20, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 2, 'agent_id' => 10, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 3, 'agent_id' => 30, 'zendesk_view_id' => 'view1']),
        ]);
        $rre = new RoundRobinEngine();
        $rre->agentOrders = collect([
            $tagViewOne = (string) (new OrderTag("view1", null)) => collect([10, 111, 112, 20, 30]),
            $tagViewTwo = (string) (new OrderTag("view2", null)) => collect([111, 112])
        ]);

        $rre->setOrdersPerView($assignments);
        
        $this->assertSame([111, 112, 20, 10, 30], $rre->agentOrders[$tagViewOne]->all());
        $this->assertSame([111, 112], $rre->agentOrders[$tagViewTwo]->all());
    }

    public function test_setOrdersPerView_akanMereorderTagTanpaViewDenganAssignmentTerLatest()
    {
        $assignments = AssignmentCollection::make([
            factory(Assignment::class)->make(['id' => 1, 'agent_id' => 10, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 2, 'agent_id' => 20, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 3, 'agent_id' => 30, 'zendesk_view_id' => 'view1']),
            factory(Assignment::class)->make(['id' => 4, 'agent_id' => 20, 'zendesk_view_id' => 'viewX']),
            factory(Assignment::class)->make(['id' => 5, 'agent_id' => 10, 'zendesk_view_id' => 'viewX'])
        ]);

        $rre = new RoundRobinEngine();
        $rre->agentOrders = collect([
            $tagNoView =(string)  (new OrderTag(null, "group1")) => collect([10, 20, 30]),
            $tagNoViewTwo =(string)  (new OrderTag(null, "group2")) => collect([20, 10, 30]),
        ]);

        $rre->setOrdersPerView($assignments);

        $this->assertSame([30, 20, 10], $rre->agentOrders[$tagNoView]->all());
        $this->assertSame([30, 20, 10], $rre->agentOrders[$tagNoViewTwo]->all());
    }

    public function test_assignTicket_hanyaAkanMencatatAssignmentJikaTiketKetemuAgentPilihannya()
    {
        // Arrange
        list($ticketOne, $ticketTwo, $ticketThree) = app(TicketFactory::class)->unassigned()->makeMany(3);
        
        /** @var \App\Services\Assignments\RoundRobinEngine&\PHPUnit\Framework\MockObject\MockObject $rre */
        $rre = $this->getMockBuilder(RoundRobinEngine::class)
                    ->setMethodsExcept(['assignTicket'])
                    ->getMock();
        
        // Assert
        $rre->expects($this->exactly(3))
        ->method('chooseEligibleAgent')
        ->withConsecutive(
            [$this->identicalTo($ticketOne)], 
            [$this->identicalTo($ticketTwo)], 
            [$this->identicalTo($ticketThree)])
        ->willReturn(
            $agentOne = factory(Agent::class)->make(["id" => 11]), 
            null, 
            $agentThree = factory(Agent::class)->make(["id" => 13])
        );
        $rre->expects($this->exactly(2))
        ->method('recordAssignment')
        ->withConsecutive(
            [$this->identicalTo($agentOne), $this->identicalTo($ticketOne)],
            [$this->identicalTo($agentThree), $this->identicalTo($ticketThree)],
        );

        // Act
        $rre->assignTicket($ticketOne);
        $rre->assignTicket($ticketTwo);
        $rre->assignTicket($ticketThree);
    }

    public function test_AssignTicket_hanyaAkanMengassignTiketJikaTiketBelumTerassignSebelumnya() 
    {  
        // Arrange
        list($ticketOne, $ticketTwo, $ticketThree) = app(TicketFactory::class)->unassigned()->makeMany(3);

        /** @var \App\Services\Assignments\RoundRobinEngine&\PHPUnit\Framework\MockObject\MockObject $rre */
        $rre = $this->getMockBuilder(RoundRobinEngine::class)
                    ->setMethodsExcept(['assignTicket'])
                    ->getMock();
        $rre->method('isRetriableTicket')->willReturn(null);
        $rre->method('chooseEligibleAgent')->willReturn($agentOne = factory(Agent::class)->make(["id" => 11]));
        
        // Assert
        $rre->expects($this->exactly(3))
            ->method('checkAlreadyAssigned')
            ->withConsecutive(
                [$this->identicalTo($ticketOne)], 
                [$this->identicalTo($ticketTwo)], 
                [$this->identicalTo($ticketThree)])
            ->willReturnOnConsecutiveCalls(false, true, false);                                                                        
        $rre->expects($this->exactly(2))
            ->method('recordAssignment')
            ->withConsecutive(
                [$this->identicalTo($agentOne), $this->identicalTo($ticketOne)],
                [$this->identicalTo($agentOne), $this->identicalTo($ticketThree)],
            );
        
        // Act
        $rre->assignTicket($ticketOne);
        $rre->assignTicket($ticketTwo);
        $rre->assignTicket($ticketThree);
    }

    public function test_assignTicket_akanMengassignRetriedAssignmentTanpaMerotateOrders()
    {
        // Arrange
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $retriableTicket */
        $retriableTicket = $this->createMock(Ticket::class);
        $retriableTicket->method('isAssignable')->willReturn(true);
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $notRetriableTicket */
        $notRetriableTicket = $this->createMock(Ticket::class);
        $notRetriableTicket->method('isAssignable')->willReturn(true);

        /** @var \App\Services\Assignments\RoundRobinEngine&\PHPUnit\Framework\MockObject\MockObject $rre */
        $rre = $this->getMockBuilder(RoundRobinEngine::class)->setMethodsExcept(['assignTicket'])->getMock();
        $rre->method('chooseEligibleAgent')->willReturn($eligibleAgent = factory(Agent::class)->make());

        //Assert
        $rre->expects($this->exactly(2))
            ->method('isRetriableTicket')
            ->willReturnOnConsecutiveCalls($matchedRetriedAgent = factory(Agent::class)->make(), null);
        $rre->expects($this->atLeastOnce())
            ->method('recordAssignment')
            ->withConsecutive(
                [$this->identicalTo($matchedRetriedAgent), $this->identicalTo($retriableTicket), $this->matches(Agent::RETRIED_ASSIGNMENT)]
            );
        $rre->expects($this->once())
            ->method('rotateAgentOrders')
            ->with($this->identicalTo($notRetriableTicket));

        // Act
        $rre->assignTicket($retriableTicket);
        $rre->assignTicket($notRetriableTicket);
    }   

    /**
     * @testWith
     *        ["ordertag1", [12, 11], 12]
     *        ["ordertag1", [11, 12], 11]
     *        ["ordertag1", [13, 12, 100], 13]
     *        ["ordertag1rotate", [11, 12, 13], 12]
     */
    public function test_chooseEligibleAgent_akanMereturnAgentPertamaDariOrderTagTiketnya(string $orderTag, array $order, int $expectedOutput)
    {
        //Arrange
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $ticketOne*/
        $ticketOne = $this->createPartialMock(Ticket::class, ["getOrderIdentifier"]);
        $ticketOne->method('getOrderIdentifier')->willReturn((new OrderTag())->setName($orderTag));

        $agentOne = $this->createPartialMock(Agent::class, ['prepareAssignment']);
        $agentOne->method('prepareAssignment')->willReturn(true);
        $agentOne->id = 11;
        $agentTwo = $this->createPartialMock(Agent::class, ['prepareAssignment']);
        $agentTwo->method('prepareAssignment')->willReturn(true);
        $agentTwo->id = 12;
        $agentThree = $this->createPartialMock(Agent::class, ['prepareAssignment']);
        $agentThree->method('prepareAssignment')->willReturn(true);
        $agentThree->id = 13;

        $rre = new RoundRobinEngine();
        $rre->agents = AgentCollection::make([$agentOne,$agentTwo,$agentThree]);
        $rre->agentOrders = collect([
            "ordertag1" => collect($order),
            "ordertag1rotate" => collect($order)->rotate(1)
        ]);

        // Act
        $eligibleAgent = $rre->chooseEligibleAgent($ticketOne);
        
        // Assert
        $this->assertInstanceOf(Agent::class, $eligibleAgent);
        $this->assertSame($expectedOutput, $eligibleAgent->id);
    }

    public function test_ChooseEligibleAgent_WillReturnAgentWithoutViewIfNoViewFound()
    {
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $ticketFromViewWithoutAgentsRegistered */
        $ticketFromViewWithoutAgentsRegistered = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketFromViewWithoutAgentsRegistered->ticket = (object) ["id" => 1];
        $ticketFromViewWithoutAgentsRegistered->method('getOrderIdentifier')->willReturn((new OrderTag())->setName("orderWithoutAgentsRegistered"));
        $ticketFromViewWithoutAgentsRegistered->ticket = (object) ["id" => 2];
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $ticketFromViewNotExist */
        $ticketFromViewNotExist = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketFromViewNotExist->method('getOrderIdentifier')->willReturn((new OrderTag())->setName("orderNotExist"));
        $ticketFromViewNotExist->ticket = (object) ["id" => 3];
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $ticketWithoutView */
        $ticketWithoutView = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketWithoutView->ticket = (object) ["id" => 4];
        /** @var \App\Services\Zendesk\Ticket&\PHPUnit\Framework\MockObject\MockObject $successTicket */
        $successTicket = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $successTicket->method('getOrderIdentifier')->willReturn((new OrderTag())->setName("order1"));
        $successTicket->ticket = (object) ["id" => 5];
        
        $agentOne = $this->createPartialMock(Agent::class, ['prepareAssignment']);
        $agentOne->method('prepareAssignment')->willReturn(true);
        $agentOne->id = 11;
        $agentTwo = $this->createPartialMock(Agent::class, ['prepareAssignment']);
        $agentTwo->method('prepareAssignment')->willReturn(true);
        $agentTwo->id = 12;

        $rre = new RoundRobinEngine();
        $rre->agents = new AgentCollection([$agentOne,$agentTwo]);
        $rre->agentOrders = collect([
            "orderWithoutAgentsRegistered" => collect([1001, 1002]),
            "order1" => collect([12, 11])
        ]);

        $a = $rre->chooseEligibleAgent($ticketFromViewWithoutAgentsRegistered);
        $b = $rre->chooseEligibleAgent($ticketFromViewNotExist);
        $c = $rre->chooseEligibleAgent($ticketWithoutView);
        $d = $rre->chooseEligibleAgent($successTicket);

        $this->assertNull($a);
        $this->assertNull($b);
        $this->assertNull($c);
        $this->assertSame(12, $d->id);
    }

    public function test_addRetriedAssignment_akanMendaftarTicketYangAkanDiAssignKeSpesifikAgent()
    {
        $assignmentOne = factory(Assignment::class)->make(["agent_id" => 1, "zendesk_ticket_id" => 11]);
        $assignmentTwo = factory(Assignment::class)->make(["agent_id" => 2, "zendesk_ticket_id" => 12]);
        $assignmentThree = factory(Assignment::class)->make(["agent_id" => 3, "zendesk_ticket_id" => 11]);
        $assignments = AssignmentCollection::make([$assignmentOne, $assignmentTwo]);
        // $ticket = app(TicketFactory::class)->id(12)->make();

        $rre = new RoundRobinEngine();
        $rre->addRetriedAssignments($assignments);
        
        $this->assertSame(1, $rre->retriedAssignments->get(11));
        $this->assertSame(2, $rre->retriedAssignments->get(12));
        $this->assertTrue($rre->retriedAssignments->contains(1));
        $this->assertTrue($rre->retriedAssignments->contains(2));
        $this->assertFalse($rre->retriedAssignments->contains(3));
    }

    /**
     * @testWith
     *        [10, 1]
     *        [11, 2]
     *        [12, 3]
     */
    public function test_isRetriableTicket_hanyaAkanMereturnTicketDanAgentYangSudahTerdaftar($id, $resultAgentId)
    {
        $ticket = app(TicketFactory::class)->id($id)->make();
        $ticketNotRetriable = app(TicketFactory::class)->id(100)->make();
        $ticketRetriedNoAgent = app(TicketFactory::class)->id(14)->make();

        $rre = new RoundRobinEngine();
        $rre->retriedAssignments = collect([10 => 1, 11 => 2, 12 => 3, 14 => 5]);
        $rre->agents = AgentCollection::make([
            factory(Agent::class)->make(["id" => 1]), 
            factory(Agent::class)->make(["id" => 2]), 
            factory(Agent::class)->make(["id" => 3]), 
            factory(Agent::class)->make(["id" => 4])
        ]);
        
        $agentResult = $rre->isRetriableTicket($ticket);
        $ticketNotRetriedAgentRegisteredResult = $rre->isRetriableTicket($ticketNotRetriable);
        $ticketRetriedButNoAgentResult = $rre->isRetriableTicket($ticketRetriedNoAgent);

        $this->assertInstanceOf(Agent::class, $agentResult);
        $this->assertSame($resultAgentId, $agentResult->id);
        $this->assertNull($ticketNotRetriedAgentRegisteredResult);
        $this->assertNull($ticketRetriedButNoAgentResult);
    }
}
