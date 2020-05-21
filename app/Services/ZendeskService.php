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

    public function getAssigneeNames($key = null) {
        $users = cache()->remember("users", 24 * 60 * 7, function () {
            $type = "admin";
            $response = $this->client->search()->find("type:user role:$type role:agent", ['sort_by' => 'updated_at']);

            return $response->results;
        });

        $agentByKey = collect($users)->keyBy("id")->pluck("name", "id");

        if ($key != null) {
            return $agentByKey->get($key); 
        }

        return $agentByKey->toArray();
    }

    public function getGroupNames($key = null) {
        $groups = cache()->remember("groups", 24 * 60 * 7, function () {
            $response = $this->client->groups()->findAll();
            return $response->groups;
        });
        $groupByKey = collect($groups)->keyBy("id")->pluck("name", "id");

        if ($key != null) {
            return $groupByKey->get($key); 
        }

        return $groupByKey->toArray();
    }

    public function getCustomFieldNames($key = null) {
        $custom_field_options = cache()->remember("custom_field_options", 24 * 60 * 7, function () {
            $response = $this->client->ticketFields()->find(env("ZENDESK_AGENT_NAMES_FIELD", 360000282796));
            return $response->ticket_field->custom_field_options;
        });
        
        $customFields = collect($custom_field_options)->keyBy('id')->pluck("name", "value");  
        
        if ($key != null) {
            return $customFields->get($key); 
        }

        return $customFields->toArray();
    }
}