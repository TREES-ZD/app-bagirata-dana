<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{ 
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->belongsToMany('App\Agent', 'rules');
    }
}
