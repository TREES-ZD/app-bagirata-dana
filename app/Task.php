<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Traits\RoundRobinable;

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
                    ->get();

        return $agents->sortBy(function($a) {
                        return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 1;
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
