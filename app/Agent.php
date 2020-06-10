<?php

namespace App;

use App\Jobs\UnassignTickets;
use App\Scopes\AgentUserScope;
use Exception;
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

    public const AVAILABLE = true;
    public const UNAVAILABLE = false;

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['status'];

    protected $appends = ['fullId', 'fullName'];

    protected static $logAttributes = ['status'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AgentUserScope);

        static::updated(function($agent) {
             if ($agent->isDirty('status')) {

                if ($agent->status == self::UNAVAILABLE) {
                    UnassignTickets::dispatch($agent)->onQueue('unassignment');
                }

                AvailabilityLog::create([
                    "status" => $agent->status == self::AVAILABLE ? AvailabilityLog::AVAILABLE : AvailabilityLog::UNAVAILABLE,
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName
                ]);
            }
        });
    }

    public function getFullIdAttribute() {
        return sprintf("%s-%s-%s", $this->zendesk_agent_id, $this->zendesk_group_id, $this->zendesk_custom_field_id);        
    }

    public function getUnassignedTickets() {
        $assignedTickets = $this->assignments()->where('type', 'ASSIGNMENT')->where('response_status', 200)->get()->pluck('zendesk_ticket_id');
        $unassignedTickets = $this->assignments()
                            ->where('type', '!=', 'ASSIGNMENT')
                            ->get()
                            ->pluck('zendesk_ticket_id');

        $assignedTicketsNotUnassigned = $assignedTickets->diff($unassignedTickets);
        return $this->assignments()->where('type', 'ASSIGNMENT')->where('response_status', 200)->whereIn('zendesk_ticket_id', $assignedTicketsNotUnassigned)->get();
    }

    public function rules() {
        return $this->belongsToMany('App\Task', 'rules')->withPivot('priority');
    }

    public function assignments() {
        return $this->hasMany('App\Assignment');
    }

    public function latestAvailability() {
        return $this->hasOne('App\AvailabilityLog')->latest()->first();
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
