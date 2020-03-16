<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;


class Agent extends Model implements Sortable
{
    use Cachable;
    
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['status'];

    protected $appends = ['fullName'];
    
    public function getFullNameAttribute()
    {
        return "ha {$this->agent_id} {$this->agent_name}";
    }    
 
    public function getTest()
    {
        return "hallo";
    }    
}
