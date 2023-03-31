<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class AvailabilityLog extends Model
{
    const AVAILABLE = "Available";
    const UNAVAILABLE = "Unavailable";
    
    protected $guarded = ['id'];

     
    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
