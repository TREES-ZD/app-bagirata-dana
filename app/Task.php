<?php

namespace App;

use App\Traits\RoundRobinable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{ 
    use RoundRobinable;
    
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->belongsToMany('App\Agent', 'rules');
    }

    public function assignments()
    {
        return $this->hasMany('App\Assignment', 'zendesk_view_id', 'zendesk_view_id');
    }

    public function getAvailableAgents() {
        $agents = $this->rules()
                    ->disableCache()
                    ->where('status', true)
                    ->with(['assignments' => function($query) {
                        $query->select('agent_id', DB::raw("MAX(created_at) as assignment_created_at"))
                              ->groupBy('agent_id');
                    }])
                    ->get();

        return $agents->sortBy(function($a) {
                        return $a->assignments->first() ? $a->assignments->first()->assignment_created_at : 1;
                })->values();
    }

    public function scopeAssignable($query) {
        return $query->where('enabled', true)
        ->withCount(['rules' => function($q) {
            $q->where('rules.priority', '>', 0);
            $q->where('agents.status', true);
        }])
        ->get()
        ->filter(function($task) { return $task->rules_count > 0;});
    }
}
