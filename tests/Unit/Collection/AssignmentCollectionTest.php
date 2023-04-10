<?php

namespace Tests\Unit\Collection;

use App\Collections\AssignmentCollection;
use Mockery;
use PHPUnit\Framework\TestCase;
use App\Services\ZendeskService;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Support\Facades\Cache;

class AssignmentCollectionTest extends TestCase
{
    /**
     * @group AssignmentCollection
     * Tests the api edit form
     */
    public function test_reconcile_will_update_details_only_on_failed_tickets() {
        $failedAssignment = (object) [
            'agent_id' => 1,
            'agent_fullName' => 'ABC',
            "agent_zendesk_agent_id" => 1322,
            "agent_zendesk_group_id" => 324,
            'agent_zendesk_custom_field_id' => 4335,
            'ticket_id' => 2,
            'ticket_subject' => 'Tes',
            'view_id' => '213455',
            'type' => 'ASSIGNMENT',
            "batch" => 'abc',
        ];
        $successAssignment = (object) [
            'agent_id' => 1,
            'agent_fullName' => 'ABC',
            "agent_zendesk_agent_id" => 1322,
            "agent_zendesk_group_id" => 324,
            'agent_zendesk_custom_field_id' => 4335,
            'ticket_id' => 1,
            'ticket_subject' => 'Tes',
            'view_id' => '213455',
            'type' => 'ASSIGNMENT',
            "batch" => 'abc',
        ];

        // $jobStatus = (object) [
        //         'id' => 'v2-tes',
        //         'url' => 'tes.com', 
        //         'status' => 'completed', 
        //         'message' => 'Completed at 2023-04-03 09:13:14 +0000', 
        //         'results' => [
        //             (object)['index' => 0, 'error' => "TicketUpdateFailed", 'id' => 1, 'details' => "Follow Up Date: needed"],
        //             (object)['index' => 0, 'error' => "TicketUpdateFailed", 'id' => 2, 'details' => "Follow Up Date: needed"],
        //             (object)['index' => 0, 'error' => "TicketUpdateFailed", 'id' => 3, 'details' => "Follow Up Date: needed"]
        //             ]
        //         ];
            
        $failedTicketDetails = [
            (object)['index' => 0, 'error' => "TicketUpdateFailed", "details" => "Follow Up Date: needed", 'id' => 2]
        ];
        
        $assignments = (new AssignmentCollection([$failedAssignment, $successAssignment]))->reconcile([1,3], $failedTicketDetails);

        $this->assertSame($assignments[0]->error, 'TicketUpdateFailed');
        $this->assertSame($assignments[0]->details, 'Follow Up Date: needed');
        $this->assertSame(optional($assignments[1])->error, null);
        $this->assertSame(optional($assignments[1])->details, null);

    }

}
