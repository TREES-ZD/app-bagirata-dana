<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvailabilityLog extends Model
{
    const AVAILABLE = "Available";
    const UNAVAILABLE = "Unavailable";
    
    protected $guarded = ['id'];
}
