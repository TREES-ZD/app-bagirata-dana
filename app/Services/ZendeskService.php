<?php

namespace App\Services;

use Huddle\Zendesk\Facades\Zendesk;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Zendesk\API\HttpClient as ZendeskAPI;
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
        return collect($response->tickets);
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

        return collect($tickets);
    }

    public function getAssignableTicketsByView($viewId) {

        $page = 1;
        $tickets = collect();
        while ($page) {
            $response = Zendesk::views($viewId)->tickets(['page' => $page]);
            
            $tickets = $tickets->merge($response->tickets);
            if ($response->next_page) {
                $page++;
            } else {
                $page = null;
            }
        }

        return $tickets->filter(function($ticket) {
            return $ticket->assignee_id == null && in_array($ticket->status, ["new", "open", "pending"]);
        });
    }

    public function updateTicket(...$params) {
        return Zendesk::tickets()->update(...$params);        
    }

    public function updateManyTickets(...$params) {
        return Zendesk::tickets()->updateMany(...$params);        
    }

    public function getJobStatus($id) {
        return Zendesk::get('job_statuses/'.$id);
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

        return Zendesk::tickets()->updateManyTickets($params);
    }

    public function getGroupMemberships() {
        $groupMemberships = Cache::remember("groupMemberships", 24 * 60 * 7, function () {
            $response = Zendesk::groupMemberships()->findAll();
            $responseTwo = Zendesk::groupMemberships()->findAll(['page' => 2]);
            return array_merge($response->group_memberships, $responseTwo->group_memberships);
        });

        return $groupMemberships;
    }

    public function getUsersByKey($key = "*", $nameOnly = false) {
        $users = Cache::remember("users", 24 * 60 * 7, function () {
            $response = Zendesk::search()->find("type:user role:admin role:agent", ['sort_by' => 'updated_at']);
            $responseTwo = Zendesk::search()->find("type:user role:admin role:agent", ['sort_by' => 'updated_at', 'page' => 2]);
            return array_merge($response->results, $responseTwo->results);
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