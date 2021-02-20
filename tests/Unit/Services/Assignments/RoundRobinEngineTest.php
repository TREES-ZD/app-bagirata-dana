<?php

namespace Tests\Unit\Services\Assignments;

use Mockery;
use App\Agent;
use App\Collections\AgentCollection;
use App\Services\Agents\OrderTag;
use Tests\TestCase;
use App\Services\Assignments\RoundRobinEngine;
use App\Services\Zendesk\Ticket;
use Tests\Helper\Factory\TicketFactory;

class RoundRobinEngineTest extends TestCase
{
    public function setUp() : void {
        parent::setUp();
    }
    
    /**
     * @method App\Repositories\AssignmentRepository::getAssignmentOrders
     *
     * @return void
     */
    public function test_AddAssignableAgents_membuatOrderanPemabgianAgentnya() {   
        //  Given
        $viewOneAgentsMock = $this->createMock(AgentCollection::class);
        $viewOneAgentsMock->expects($this->atLeastOnce())
                            ->method('getAssignmentOrders')
                            ->willReturn(collect([2, 1, 3]));

        $viewTwoAgentsMock = $this->createMock(AgentCollection::class);
        $viewTwoAgentsMock->expects($this->atLeastOnce())
                            ->method('getAssignmentOrders')
                            ->willReturn(collect([5, 10, 7]));

        /** @var \App\Collections\AgentCollection&\PHPUnit\Framework\MockObject\MockObject $agentCollectionMock */
        $agentCollectionMock = $this->createMock(AgentCollection::class);
        $agentCollectionMock->method('groupByOrdersIdentifierTags')
                            ->willReturn(new AgentCollection([
                                            "ordertag1" => $viewOneAgentsMock,
                                            "ordertag2" => $viewTwoAgentsMock
                                        ]));
        
        $rre = new RoundRobinEngine();
        $rre->addAssignableAgents($agentCollectionMock);
        
        $this->assertCount(2, $rre->agentOrders);
        $this->assertSame($rre->getAgentOrders("ordertag1")->all(), [2, 1, 3]);
        $this->assertSame($rre->getAgentOrders("ordertag2")->all(), [5, 10, 7]);
        $this->assertSame($rre->getAgentOrders("ordertagX")->all(), []);
    }

    public function test_AssignTicket_hanyaAkanMengassignTicketYangSamaSekali() 
    {  
        $ticketOne = new Ticket((object) ["id" => 1, "status" => "open"]);
        $ticketTwo = new Ticket((object) ["id" => 2, "status" => "open"]);
        /** @var \App\Services\Assignments\RoundRobinEngine&\PHPUnit\Framework\MockObject\MockObject $rre */
        $rre = $this->getMockBuilder(RoundRobinEngine::class)
                    ->setMethodsExcept(['assignTicket'])
                    ->getMock();

        $rre->expects($this->exactly(3))->method('checkAlreadyAssigned')->willReturn(false, true, false);
        $rre->expects($this->exactly(3))->method('chooseEligibleAgent')->willReturn(
                                                                            $agentOne = factory(Agent::class)->make(["id" => 11]), 
                                                                            $agentTwo = factory(Agent::class)->make(["id" => 12]), 
                                                                            $agentThree = factory(Agent::class)->make(["id" => 13])
                                                                        );
        $rre->expects($this->exactly(2))->method('rotateAgentOrders')->withConsecutive(
                                                                        [$this->identicalTo($ticketOne)],
                                                                        [$this->identicalTo($ticketTwo)]
                                                                    );
        
        $resultOne = $rre->assignTicket($ticketOne);
        $resultTwo = $rre->assignTicket($ticketOne);
        $resultThree = $rre->assignTicket($ticketTwo);
        
        $this->assertTrue($resultOne);
        $this->assertFalse($resultTwo);
        $this->assertTrue($resultThree);
        $this->assertCount(2, $rre->assignmentPairs, "assignment pairs are build after two successfull assignment"); 
        $this->assertEquals([$agentOne->id, $ticketOne->id], $rre->assignmentPairs[0]);
        $this->assertEquals([$agentThree->id, $ticketTwo->id], $rre->assignmentPairs[1]);
    }

    /**
     * @testWith
     *        ["ordertag1", [12, 11], 12]
     *        ["ordertag1", [11, 12], 11]
     *        ["ordertag1", [13, 12, 100], 13]
     *        ["ordertag1rotate", [11, 12, 13], 12]
     */
    public function test_chooseEligibleAgent_WillGetAgentFromTheFirstOrderOfTicketTag(string $orderTag, array $order, int $expectedOutput)
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
        $t = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);

        $ticketFromViewWithoutAgentsRegistered = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketFromViewWithoutAgentsRegistered->ticket = (object) ["id" => 1];
        $ticketFromViewWithoutAgentsRegistered->method('getOrderIdentifier')->willReturn((new OrderTag())->setName("orderWithoutAgentsRegistered"));
        $ticketFromViewWithoutAgentsRegistered->ticket = (object) ["id" => 2];
        $ticketFromViewNotExist = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketFromViewNotExist->method('getOrderIdentifier')->willReturn((new OrderTag())->setName("orderNotExist"));
        $ticketFromViewNotExist->ticket = (object) ["id" => 3];
        $ticketWithoutView = $this->createPartialMock(Ticket::class, ['getOrderIdentifier']);
        $ticketWithoutView->ticket = (object) ["id" => 4];
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
}
