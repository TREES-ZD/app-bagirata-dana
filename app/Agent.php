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

    public const ASSIGNMENT = "assignment";

    public const REASSIGNMENT = "assignment";

    public $incrementing = false;

    protected $keyType = 'string';   

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['status'];

    protected $appends = ['fullName'];

    public function assignments() {
        return $this->hasMany('App\Assignment');
    }

    public function getFullNameAttribute()
    {
        return "{$this->zendesk_agent_name} ({$this->zendesk_group_name}, {$this->zendesk_custom_field})";
    }    
 
    public function getTest()
    {
        return "hallo";
    }    
}
