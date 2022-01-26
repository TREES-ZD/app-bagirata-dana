<?php

namespace Tests\Helper;

use Mockery;
use App\Task;
use App\Agent;
use Carbon\Carbon;
use App\Assignment;
use Tests\TestCase;
use App\Jobs\ProcessTask;
use Illuminate\Support\Str;
use Faker\Generator as Faker;
use App\Services\Zendesk\Ticket;
use App\Services\ZendeskService;
use function DeepCopy\deep_copy;
use Illuminate\Support\Facades\DB;
use App\Services\Zendesk\ZendeskWrapper;
use Illuminate\Database\Eloquent\Factory;
use App\Services\Zendesk\TicketCollection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

trait ZendeskWrapperFixture
{
    static $viewWith501AssignableTickets = "501";
    static $viewWith250AssignableTickets = "250";
    static $viewALLASSIGNED301 = "301";
    static $view101 = "101";
    static $view1 = "1";
    static $viewTICKETS567 = "300567";
    static $viewTICKETS1001TO1004 = "100104";
    static $viewTICKETS1003TO1005 = "100302";
    static $viewUnassignedOneToSix = "10016";
    static $GROUPA_ID = 11;
    static $GROUPAD_ID = 1144;
    static $GROUPBC_ID = 2233;

    public function mockZendeskWrapper()
    {
        app()->bind(ZendeskWrapper::class, function() {
            $mock = $this->createMock(ZendeskWrapper::class);
            $mock->method('listTicketsByView')->willReturnCallback(function($viewId, $args) {
                $page = isset($args['page']) ? $args['page'] : 1;

                if ($viewId == self::$viewWith501AssignableTickets && $page == 1) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 1);
                if ($viewId == self::$viewWith501AssignableTickets && $page == 2) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 2);
                if ($viewId == self::$viewWith501AssignableTickets && $page == 3) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 3);
                if ($viewId == self::$viewWith501AssignableTickets && $page == 4) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 4);
                if ($viewId == self::$viewWith501AssignableTickets && $page == 5) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 5);
                if ($viewId == self::$viewWith501AssignableTickets && $page == 6) return ZendeskFactory::dummyListTicketsResponse(range(1, 501), 6);
                if ($viewId == self::$viewWith250AssignableTickets && $page == 1) return ZendeskFactory::dummyListTicketsResponse(range(1, 250), 1);
                if ($viewId == self::$viewWith250AssignableTickets && $page == 2) return ZendeskFactory::dummyListTicketsResponse(range(1, 250), 2);
                if ($viewId == self::$viewWith250AssignableTickets && $page == 3) return ZendeskFactory::dummyListTicketsResponse(range(1, 250), 3);
                if ($viewId == self::$viewALLASSIGNED301 && $page == 1) return ZendeskFactory::dummyListAssignedTicketsResponse(range(1, 301), 1);
                if ($viewId == self::$viewALLASSIGNED301 && $page == 2) return ZendeskFactory::dummyListAssignedTicketsResponse(range(1, 301), 2);
                if ($viewId == self::$viewALLASSIGNED301 && $page == 3) return ZendeskFactory::dummyListAssignedTicketsResponse(range(1, 301), 3);
                if ($viewId == self::$viewALLASSIGNED301 && $page == 4) return ZendeskFactory::dummyListAssignedTicketsResponse(range(1, 301), 4);
                if ($viewId == self::$view101 && $page == 1) return ZendeskFactory::dummyListTicketsResponse(range(1, 101), 1);
                if ($viewId == self::$view101 && $page == 2) return ZendeskFactory::dummyListTicketsResponse(range(1, 101), 2);
                if ($viewId == self::$viewTICKETS567 && $page == 1) return ZendeskFactory::dummyListTicketsResponse([5,6,7], 1);
                if ($viewId == self::$view1 && $page == 1) return ZendeskFactory::dummyListTicketsResponse([1], 1);
                if ($viewId == self::$viewTICKETS1001TO1004 && $page == 1) return ZendeskFactory::dummyListTicketsResponse(range(1001, 1004), 1);
                if ($viewId == self::$viewTICKETS1003TO1005 && $page == 1) return ZendeskFactory::dummyListTicketsResponse([1003, 1005], 1);
                if ($viewId == self::$viewUnassignedOneToSix && $page == 1) return ZendeskFactory::dummyListUnassignedTicketsResponse(range(1, 6), 1);

                return ZendeskFactory::dummyListTicketsResponse([], 1);
            });

            $mock->method('updateManyTickets')
                ->willReturn(
                    ZendeskFactory::dummyUpdateManyTicketsResponse([1,2,3])
                );

            // Mocking seluruh panggilan ke job status akan mereturn completed pada panggilan kedua
            $mock->method('showManyJobStatuses')
                ->willReturn(
                    ZendeskFactory::dummyShowManyJobStatusesCompleted(range(1, 500)),
                );

            return $mock;
        });       
    }
}