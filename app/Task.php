<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';
    
    protected $guarded = ['id'];
}
