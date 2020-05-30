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
        return $this->rules()
                    ->disableCache()
                    ->where('status', true)
                    ->get();
    }

}
