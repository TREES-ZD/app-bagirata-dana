<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{ 
    protected $guarded = ['id'];

    public function agents()
    {
        return $this->belongsToMany('App\Agent', 'rules');
    }
}
