<?php

namespace Tests\Helper;

use stdClass;
use Illuminate\Support\Str;

use Faker\Generator as Faker;
use function DeepCopy\deep_copy;

class ZendeskFactory
{
    public static function dummyListTicketsResponse(array $idsRange, $page = 1)
    {
        $response = new stdClass();
        $subdomain = env('ZENDESK_SUBDOMAIN');
        $chunkedIdsRange = collect($idsRange)->chunk(100);
        
        $ids = $chunkedIdsRange->get($page - 1) ?: collect();
        $tickets = $ids->map(function($id) use ($subdomain) {

            return (object) [
                "url" => "https://$subdomain.zendesk.com/api/v2/tickets/$id.json",
                "id" => $id,
                "subject" => "ticket " . $id,
                "status" => "new",
                "requester_id" => app(Faker::class)->randomDigitNotNull,
                "assignee_id" => null,
                "group_id" => null,
                "custom_fields" => [
                    (object) ["id" => env('ZENDESK_AGENT_NAMES_FIELD', 360000282796), "value" => null]
                ]
            ];
        });
        $next_page = ++$page;
        $previous_page = $page - 2;
        $response->tickets = $tickets->all();
        $response->next_page = $chunkedIdsRange->get($next_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$next_page.json" : null;
        $response->previous_page = $chunkedIdsRange->get($previous_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$previous_page.json" : null;
        $response->count = count($idsRange);
        return $response;
    }

    public static function dummyListUnassignedTicketsResponse(array $idsRange, $page = 1, $assignee_id = 444444, $group_id = 555555, $agent_name = "andi")
    {
        $response = new stdClass();
        $subdomain = env('ZENDESK_SUBDOMAIN');
        $chunkedIdsRange = collect($idsRange)->chunk(100);
        
        $ids = $chunkedIdsRange->get($page - 1) ?: collect();
        $tickets = $ids->map(function($id) use ($subdomain, $group_id) {

            return (object) [
                "url" => "https://$subdomain.zendesk.com/api/v2/tickets/$id.json",
                "id" => $id,
                "subject" => "ticket " . $id,
                "status" => "open",
                "requester_id" => app(Faker::class)->randomDigitNotNull,
                "assignee_id" => null,
                "group_id" => $group_id,
                "custom_fields" => [
                    (object) ["id" => env('ZENDESK_AGENT_NAMES_FIELD', 360000282796), "value" => null]
                ]
            ];
        });
        $next_page = ++$page;
        $previous_page = $page - 2;
        $response->tickets = $tickets->all();
        $response->next_page = $chunkedIdsRange->get($next_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$next_page.json" : null;
        $response->previous_page = $chunkedIdsRange->get($previous_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$previous_page.json" : null;
        $response->count = count($idsRange);
        return $response;
    }

    public static function dummyListAssignedTicketsResponse(array $idsRange, $page = 1, $assignee_id = 444444, $group_id = 555555, $agent_name = "andi")
    {
        $response = new stdClass();
        $subdomain = env('ZENDESK_SUBDOMAIN');
        $chunkedIdsRange = collect($idsRange)->chunk(100);
        
        $ids = $chunkedIdsRange->get($page - 1) ?: collect();
        $tickets = $ids->map(function($id) use ($subdomain, $assignee_id, $group_id, $agent_name) {
            return (object) [
                "url" => "https://$subdomain.zendesk.com/api/v2/tickets/$id.json",
                "id" => $id,
                "subject" => "ticket " . $id,
                "status" => "open",
                "requester_id" => app(Faker::class)->randomDigitNotNull,
                "assignee_id" => $assignee_id,
                "group_id" => $group_id,
                "custom_fields" => [
                    (object) ["id" => env('ZENDESK_AGENT_NAMES_FIELD', 360000282796), "value" => $agent_name]
                ]
            ];
        });
        $next_page = ++$page;
        $previous_page = $page - 2;
        $response->tickets = $tickets->all();
        $response->next_page = $chunkedIdsRange->get($next_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$next_page.json" : null;
        $response->previous_page = $chunkedIdsRange->get($previous_page - 1) ? "https://$subdomain.zendesk.com/api/v2/tickets/$previous_page.json" : null;
        $response->count = count($idsRange);
        return $response;
    }

    public static function dummyShowManyJobStatusesCompleted(array $idsRange, $page = 1)
    {
        $response = new stdClass();
        $subdomain = env('ZENDESK_SUBDOMAIN');
        $chunkedIdsRange = collect($idsRange)->chunk(100);

        $job_statuses = $chunkedIdsRange->map(function($ids) use ($subdomain) {
            $results = $ids->map(function($id) use ($subdomain) {
                return (object) [
                    "action" => "update",
                    "status" => "Updated",
                    "success" => true,
                    "id" => $id
                ];
            });

            $id = md5((string) Str::uuid());
            return (object) [
                "id" => $id,
                "url" => "https://$subdomain.zendesk.com/api/v2/job_statuses/$id.json",
                "total" => $results->count(),
                "progress" => $results->count(),
                "status" => "completed",
                "message" => "Completed at 2020-07-09 03:44:55 +0000",
                "results" => $results->all(),
            ];
        });
        $next_page = ++$page;
        $previous_page = $page - 2;
        $response->job_statuses = $job_statuses->all();
        $response->next_page = $chunkedIdsRange->get($next_page - 1) ? "https://$subdomain.zendesk.com/api/v2/job_statuses/$next_page.json" : null;
        $response->previous_page = $chunkedIdsRange->get($previous_page - 1) ? "https://$subdomain.zendesk.com/api/v2/job_statuses/$previous_page.json" : null;
        $response->count = count($idsRange);
        return $response;
    }
    
    public static function dummyUpdateManyTicketsResponse($idsRange, $page = 1)
    {
        $response = new stdClass();
        $subdomain = env('ZENDESK_SUBDOMAIN');
        $chunkedIdsRange = collect($idsRange)->chunk(100);
        
        $ids = $chunkedIdsRange->get($page - 1) ?: collect();
        $results = $ids->map(function($id) use ($subdomain) {
            return (object) [
                "action" => "update",
                "status" => "Updated",
                "success" => true,
                "id" => $id
            ];
        });

        $id = md5((string) Str::uuid());
        $job_status =  (object) [
            "id" => $id,
            "url" => "https://$subdomain.zendesk.com/api/v2/job_statuses/$id.json",
            "total" => $results->count(),
            "progress" => $results->count(),
            "status" => "completed",
            "message" => "Completed at 2020-07-09 03:44:55 +0000",
            "results" => $results->all(),
        ];

        $next_page = ++$page;
        $previous_page = $page - 2;
        $response->job_status = $job_status;
        $response->next_page = $chunkedIdsRange->get($next_page - 1) ? "https://$subdomain.zendesk.com/api/v2/job_statuses/$next_page.json" : null;
        $response->previous_page = $chunkedIdsRange->get($previous_page - 1) ? "https://$subdomain.zendesk.com/api/v2/job_statuses/$previous_page.json" : null;
        $response->count = count($idsRange);
        return $response;
    }
}