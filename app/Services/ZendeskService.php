<?php

namespace App\Services;

use Zendesk\API\HttpClient as ZendeskAPI;

class ZendeskService
{
    public function __construct() {
        $subdomain = env("ZENDESK_SUBDOMAIN", "contreesdemo11557827937");
        $username  = env("ZENDESK_USERNAME", "eldien.hasmanto@treessolutions.com");
        $token     = env("ZENDESK_TOKEN", "2HJtvL35BSsWsVR4b3ZCxvYhLGYcAacP2EyFKGki"); // replace this with your token

        $this->client = new ZendeskAPI($subdomain);
        $this->client->setAuth('basic', ['username' => $username, 'token' => $token]);
    }

    public function getGroupMemberships($key = null) {
        $groupMemberships = cache()->remember("groupMemberships", 24 * 60 * 7, function () {
            $response = $this->client->groupMemberships()->findAll();
            return $response->group_memberships;
        });

        return $groupMemberships;
    }

    // Should be refactor to -ByKey
    public function getUsers($key = null, $nameOnly = false) {
        $users = cache()->remember("users", 24 * 60 * 7, function () {
            $type = "admin";
            $response = $this->client->search()->find("type:user role:$type role:agent", ['sort_by' => 'updated_at']);

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
            $response = $this->client->groups()->findAll();
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
            $response = $this->client->ticketFields()->find(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796));
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