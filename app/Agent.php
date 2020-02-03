<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Agent extends Model implements Sortable
{
    use SortableTrait;

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];    
 
    public function getTest()
    {
        return "hallo";
    }    
}
