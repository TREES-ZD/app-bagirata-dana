<?php

namespace App\Services;

use App\Agent;
use Illuminate\Support\Arr;
use App\Services\Zendesk\Ticket;
use App\Services\Zendesk\JobStatus;
use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Support\Facades\Cache;
use Zendesk\API\HttpClient as ZendeskAPI;
use App\Services\Zendesk\TicketCollection;
use App\Services\Zendesk\JobStatusCollection;
use phpDocumentor\Reflection\Types\Callable_;

class ZendeskService
{
    public const AGENT_IDS = "zendesk_agent_ids";
    public const GROUP_IDS = "zendesk_group_ids";
    public const CUSTOM_FIELD_IDS = "zendesk_custom_field_ids";

    public const ALL = "*";

    public const SHOW_NAME_ONLY = true;
    public const SHOW_FULL = false;
    
    private $filters = [];

    public function __construct() {
    }

    public function filters($filters) {
        $filters = is_array($filters) && count($filters) > 0 ? $filters : ["zendesk_agent_ids" => null, "zendesk_group_ids" => null, "zendesk_custom_field_ids" => null];

        return $this;
    }

    public function filterUsers($filter) {
        if (!Arr::accessible($filter)) {
            $filter = Arr::wrap($filter);
        }

        $this->filters[self::AGENT_IDS] = $filter;
        return $this;
    }    

    public function filterGroups($filter) {
        if (!Arr::accessible($filter)) {
            $filter = Arr::wrap($filter);
        }

        $this->filters[self::GROUP_IDS] = $filter;
        return $this;
    }    

    public function filterCustomFields($filter) {
        if (!Arr::accessible($filter)) {
            $filter = Arr::wrap($filter);
        }

        $this->filters[self::CUSTOM_FIELD_IDS] = $filter;
        return $this;
    }    

    public function refresh() {
        Cache::forget('groupMemberships');
        Cache::forget('users');
        Cache::forget('groups');
        Cache::forget('custom_field_options');
    }

    public function getPossibleAgents() {
        $agentByKey = $this->getUsersByKey(static::ALL, static::SHOW_FULL);
        $groupByKey = $this->getGroupsByKey(static::ALL, static::SHOW_FULL);
        $customFields = $this->getCustomFieldsByValue(static::ALL, static::SHOW_FULL);
        $groupMemberships = collect($this->getGroupMemberships());
        
        $agents = $groupMemberships
                ->crossJoin($customFields)
                ->reject(function($membershipAndCustomField) use ($agentByKey, $groupByKey) {
                    $membership = $membershipAndCustomField[0];
                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);
                    
                    return !($agent && $group);
                })
                ->mapWithKeys(function($membershipAndCustomField) use ($agentByKey, $groupByKey) {
                    $membership = $membershipAndCustomField[0];
                    $customField = $membershipAndCustomField[1];

                    $agent = $agentByKey->get($membership->user_id);
                    $group = $groupByKey->get($membership->group_id);

                    $full_id = sprintf("%s-%s-%s", $agent->id, $group->id, $customField->value);
                    return [
                        $full_id => [
                            "zendesk_agent_id" => $agent->id,
                            "zendesk_agent_name" => $agent->name,
                            "zendesk_group_id" => $group->id,
                            "zendesk_group_name" => $group->name,
                            "zendesk_custom_field_id" => $customField->value,
                            "zendesk_custom_field_name" => $customField->name,
                        ]
                    ]; 
                });      

        return $agents; 
    }

    public function getTicketsByIds(array $ids) {
        $response = Zendesk::tickets()->findMany($ids);
        return (new TicketCollection($response->tickets))->map(function($ticket) {
            return new Ticket($ticket);
        });
    }

    public function getViews() {
        // $tickets = Cache::remember("views", -1, function (){
        //     $response = Zendesk::views()->findAll();
        //     return $response->views;
        // });

        $response = Zendesk::views()->findAll();
        return $response->views;

        // return $tickets;
    }

    public function getTicketsByView($viewId) {
        $response = Zendesk::views($viewId)->tickets();
        $tickets = $response->tickets;

        return (new TicketCollection($tickets))->map(function($ticket) {
            return new Ticket($ticket);
        });
    }

    /**
     * Undocumented function
     *
     * @param [type] $viewId
     * @return TicketCollection
     */
    public function getAssignableTicketsByView($viewId) {
        $page = 1;
        $tickets = new TicketCollection();
        while ($page && $page <= 5 || $page > 2 && $tickets->isEmpty()) {
            $response = Zendesk::views($viewId)->tickets(['page' => $page]);
            
            $ticketResults = collect($response->tickets)->map(function($ticket) {
                return new Ticket($ticket);
            });

            $tickets = $tickets->merge($ticketResults->all());
            if ($response->next_page) {
                $page++;
            } else {
                $page = null;
            }
        }

        return $tickets->unique->id()->filter->isAssignable();
    }

    public function updateTicket(...$params) {
        return Zendesk::tickets()->update(...$params);
    }

    public function updateManyTickets($tickets) {
        $response = Zendesk::tickets()->updateMany($tickets->all());
        return new JobStatus($response->job_status);
    }

    public function getJobStatus($id) {
        return Zendesk::get('job_statuses/'.$id);
    }

    public function getJobStatuses($ids) {
        $response = Zendesk::jobStatuses()->findMany($ids);

        return (new JobStatusCollection($response->job_statuses))->map(function($jobStatus) {
            return new JobStatus($jobStatus);
        });
    }

    public function unassignTickets($ids, $agent_id, $agent_fullName) {
        if (count($ids) < 1) {
            return;
        }

        $params = [
            "ids" => $ids,
            "custom_fields" => [
                [
                "id" => env("ZENDESK_AGENT_NAMES_FIELD", 360000282796),
                "value" => null
                ]
            ],
            "comment" =>  [
                "body" => "BAGIRATA Agent Unavailable: " . $agent_fullName,
                "author_id" => $agent_id,
                "public" => false
            ]
        ];

        $response = Zendesk::tickets()->updateMany($params);

        return new JobStatus($response->job_status);
    }

    public function getGroupMemberships() {
        $groupMemberships = Cache::remember("groupMemberships", 24 * 60 * 7, function () {
            $response = Zendesk::groupMemberships()->findAll();
            $responseTwo = Zendesk::groupMemberships()->findAll(['page' => 2]);
            $responseThree = Zendesk::groupMemberships()->findAll(['page' => 3]);
            return array_merge($response->group_memberships, $responseTwo->group_memberships, $responseThree->group_memberships);
        });

        return $groupMemberships;
    }

    public function getAssignedTickets(Agent $agent) {
        $page = 1;
        $tickets = new TicketCollection();
        while ($page && $page <= 10) {
            $response = Zendesk::search()->find("type:ticket assignee:$agent->zendesk_agent_id group:$agent->zendesk_group_id tags:$agent->zendesk_custom_field_id", ["page" => $page]);
            
            $tickets = $tickets->merge($response->results);
            if ($response->next_page) {
                $page++;
            } else {
                $page = null;
            }
        }

        return $tickets->map(function($ticket) {
            return new Ticket($ticket);
        });
    }

    public function getUsersByKey($key = "*", $nameOnly = false) {
        $users = Cache::remember("users", 24 * 60 * 7, function () {
            $response = Zendesk::search()->find("type:user role:admin role:agent", ['sort_by' => 'updated_at']);
            $responseTwo = Zendesk::search()->find("type:user role:admin role:agent", ['sort_by' => 'updated_at', 'page' => 2]);
            $responseThree = Zendesk::search()->find("type:user role:admin role:agent", ['sort_by' => 'updated_at', 'page' => 3]);
            return array_merge($response->results, $responseTwo->results, $responseThree->results);
        });

        $users = $this->filter(static::AGENT_IDS, $users);

        $agentByKey = collect($users)->keyBy("id");

        if ($nameOnly) {
            $agentByKey = $agentByKey->pluck("name", "id");
        }

        if ($key != "*") {
            return $agentByKey->get($key); 
        }

        return $agentByKey;
    }

    // Should be refactor to -ByKey
    public function getGroupsByKey($key = "*", $nameOnly = false) {
        $groups = Cache::remember("groups", 24 * 60 * 7, function () {
            $response = Zendesk::groups()->findAll();
            return $response->groups;
        });

        $groups = $this->filter(static::GROUP_IDS, $groups);

        $groupByKey = collect($groups)->keyBy("id");

        if ($nameOnly) {
            $groupByKey = $groupByKey->pluck("name", "id");
        }

        if ($key != "*") {
            return $groupByKey->get($key); 
        }

        return $groupByKey;
    }

    public function getCustomFieldsByValue($value = "*", $nameOnly = false) {
        $custom_field_options = Cache::remember("custom_field_options", 24 * 60 * 7, function () {
            $response = Zendesk::ticketFields()->find(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796));
            return $response->ticket_field->custom_field_options;
        });

        $custom_field_options = $this->filter(static::CUSTOM_FIELD_IDS, $custom_field_options);

        $customFields = collect($custom_field_options)->keyBy('value');

        if ($nameOnly) {
            $customFields = $customFields->pluck("name", "value");  
        }
        
        if ($value != "*") {
            return $customFields->get($value); 
        }

        return $customFields;
    }


    private function filter($type, $resources) {
        if (!isset($this->filters[$type]) || in_array(static::ALL, $this->filters[$type])) {
            return $resources;
        }

        if (!Arr::accessible($this->filters[$type])) {
            $this->filters[$type] = [$this->filters[$type]];
        }

        if (count($this->filters[$type]) < 1) {
            return [];
        }

        return array_filter($resources, function($resource) use ($type) {
            if ($type == static::AGENT_IDS || $type == static::GROUP_IDS) {
                return in_array($resource->id, $this->filters[$type]);
            } else if ($type == static::CUSTOM_FIELD_IDS) {
                return in_array($resource->value, $this->filters[$type]);
            }

            return true;
        });
    }
}