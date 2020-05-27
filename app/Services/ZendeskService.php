<?php

namespace App\Services;

use Huddle\Zendesk\Facades\Zendesk;
use Zendesk\API\HttpClient as ZendeskAPI;

class ZendeskService
{
    public function __construct() {
    }

    public function getTicketsByIds(array $ids) {
        $response =Zendesk::tickets()->findMany($ids);
        return $response->tickets;
    }

    public function getViews() {
        $tickets = cache()->remember("views", 24 * 60 * 7, function (){
            $response = Zendesk::views()->findAll();
            return $response->views;
        });

        return $tickets;
    }

    public function getTicketsByView($viewId) {
        $response = Zendesk::views($viewId)->tickets();
        $tickets = $response->tickets;

        return $tickets;
    }

    public function updateTicket(...$params) {
        return Zendesk::tickets()->update(...$params);        
    }

    public function getGroupMemberships($key = null) {
        $groupMemberships = cache()->remember("groupMemberships", 24 * 60 * 7, function () {
            $response = Zendesk::groupMemberships()->findAll();
            return $response->group_memberships;
        });

        return $groupMemberships;
    }

    // Should be refactor to -ByKey
    public function getUsers($key = null, $nameOnly = false) {
        $users = cache()->remember("users", 24 * 60 * 7, function () {
            $type = "admin";
            $response = Zendesk::search()->find("type:user role:$type role:agent", ['sort_by' => 'updated_at']);

            return $response->results;
        });

        $agentByKey = collect($users)->keyBy("id");

        if ($nameOnly) {
            $agentByKey = $agentByKey->pluck("name", "id");
        }

        if ($key != null) {
            return $agentByKey->get($key); 
        }

        return $agentByKey->toArray();
    }

    // Should be refactor to -ByKey
    public function getGroups($key = null, $nameOnly = false) {
        $groups = cache()->remember("groups", 24 * 60 * 7, function () {
            $response = Zendesk::groups()->findAll();
            return $response->groups;
        });
        $groupByKey = collect($groups)->keyBy("id");

        if ($nameOnly) {
            $groupByKey = $groupByKey->pluck("name", "id");
        }

        if ($key != null) {
            return $groupByKey->get($key); 
        }

        return $groupByKey->toArray();
    }

    // Should be refactor to -ByKey
    public function getCustomFields($key = null, $nameOnly = false) {
        $custom_field_options = cache()->remember("custom_field_options", 24 * 60 * 7, function () {
            $response = Zendesk::ticketFields()->find(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796));
            return $response->ticket_field->custom_field_options;
        });
        
        $customFields = collect($custom_field_options)->keyBy('value');

        if ($nameOnly) {
            $customFields = $customFields->pluck("name", "value");  
        }
        
        if ($key != null) {
            return $customFields->get($key); 
        }

        return $customFields->toArray();
    }
}