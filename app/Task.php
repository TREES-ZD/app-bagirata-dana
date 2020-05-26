<?php

namespace App;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{ 
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->belongsToMany('App\Agent', 'rules');
    }

    public function matchAssignments(Collection $tickets) {
        $agents = $this->rules()
                        ->disableCache()
                        ->where('status', true)
                        ->with(['assignments'])
                        ->get()
                        ->sortBy(function($a) {
                            return $a->assignments->last() ? $a->assignments->last()->created_at->timestamp : 0;
                        });              
        
        if ($agents->count() < 1) {
            return collect();
        } 
        $totalAgents = $agents->count();

        $match = $tickets->map(function($ticket, $index) use ($agents) {
            $agentNum = ($index % $agents->count());
            $agent = $agents->get($agentNum);
            return collect([
                "agent" => $agent,
                "ticket" => $ticket
            ]);
        });
        return $match;
    }
}
