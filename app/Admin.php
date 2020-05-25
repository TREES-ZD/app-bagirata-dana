<?php

namespace App;

use Encore\Admin\Auth\Database\Administrator;

class Admin extends Administrator
{

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    protected $casts = [
        "zendesk_assignee_ids" => "array",
        "zendesk_group_ids" => "array",
        "zendesk_custom_field_ids" => "array"
    ];
}