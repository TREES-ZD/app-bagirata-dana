<?php

namespace App;

use App\Jobs\UnassignTickets;
use App\Scopes\AgentUserScope;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Spatie\Activitylog\Traits\LogsActivity;

class Agent extends Model implements Sortable
{
    use Cachable;
    
    use SortableTrait;

    public const ASSIGNMENT = "ASSIGNMENT";

    public const REASSIGNMENT = "REASSIGNMENT";

    public const UNASSIGNMENT = "UNASSIGNMENT";

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['status'];

    protected $appends = ['fullName'];

    protected static $logAttributes = ['status'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AgentUserScope);

        static::updated(function($agent) {

            if ($agent->isDirty('status')) {

                if ($agent->status == false) {
                    UnassignTickets::dispatchNow($agent);
                }

                AvailabilityLog::create([
                    "status" => $agent->status ? "Available" : "Unavailable",
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName
                ]);
            }
        });
    }

    public function rules() {
        return $this->belongsToMany('App\Task', 'rules')->withPivot('priority');
    }

    public function assignments() {
        return $this->hasMany('App\Assignment');
    }

    public function getFullNameAttribute()
    {
        return "{$this->zendesk_custom_field_name} ({$this->zendesk_group_name}, {$this->zendesk_agent_name})";
    }    
 
    public function getTest()
    {
        return "hallo";
    }    
}
