<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class Group extends Model
{
    use Cachable;

    public $incrementing = false;

    protected $keyType = 'string';   
}
