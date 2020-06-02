<?php

namespace App\Scopes;

use App\Admin as AppAdmin;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Builder;

class AgentUserScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = Admin::user();
        if (!Admin::user()) {
            return;
        }

        $assignee_ids = json_decode($user->zendesk_assignee_ids);
        if ($assignee_ids) {
            $builder->whereIn('zendesk_assignee_id', $assignee_ids);
        }

        $group_ids = json_decode($user->zendesk_group_ids);
        if ($group_ids) {
            $builder->whereIn('zendesk_group_id', $group_ids);
        }

        $custom_field_ids = json_decode($user->zendesk_custom_field_ids);
        if ($custom_field_ids) {
            $builder->whereIn('zendesk_custom_field_id', $custom_field_ids);
        }        
    }
}