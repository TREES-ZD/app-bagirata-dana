<?php

namespace App\Models;

use App\Collections\AgentCollection;
use App\Jobs\Agent\UnassignTickets;
use App\Scopes\AgentUserScope;
use Exception;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Spatie\Activitylog\Traits\LogsActivity;
use Tests\Integration\Jobs\UnassignTicketsTest;

class Agent extends Model implements Sortable
{
    use Cachable;
    
    use SortableTrait;

    public const ASSIGNMENT = "ASSIGNMENT";

    public const ASSIGNMENT_PRIORITY = "ASSIGNMENT_PRIORITY";

    public const REASSIGNMENT = "REASSIGNMENT";

    public const UNASSIGNMENT = "UNASSIGNMENT";

    public const OBSERVED_UNASSIGNMENT = "OBSERVED_UNASSIGNMENT";

    public const AVAILABLE = true;
    public const UNAVAILABLE = false;

    public const CUSTOM_STATUS_AVAILABLE = 'AVAILABLE';
    public const CUSTOM_STATUS_AWAY = 'AWAY';
    public const CUSTOM_STATUS_UNAVAILABLE = 'UNAVAILABLE';

    public $sortable = [
        'order_column_name' => 'priority',
        'sort_when_creating' => true,
    ];

    protected $fillable = ['status', 'custom_status'];

    protected $appends = ['fullId', 'fullName'];

    protected static $logAttributes = ['status', 'custom_status'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AgentUserScope);

        static::updated(function($agent) {
           //  if ($agent->isDirty('status')) {
            //     $custom_status = '';
            //     switch ($agent->status) {
            //         case self::AVAILABLE:
            //             $custom_status = Agent::AVAILABLE; 
            //             break;
            //         default:
            //             $custom_status = Agent::UNAVAILABLE;
            //             break;

            //     AvailabilityLog::create([
            //         "status" => $agent->status == self::AVAILABLE ? AvailabilityLog::AVAILABLE : AvailabilityLog::UNAVAILABLE,
            //         "agent_id" => $agent->id,
            //         "agent_name" => $agent->fullName,
            //         'custom_status' => $custom_status
            //     ]);
            // }

            
            if ($agent->isDirty('custom_status')) {
                $status = '';
                switch ($agent->custom_status) {
                    case self::CUSTOM_STATUS_AVAILABLE:
                        $status = AvailabilityLog::AVAILABLE; 
                        break;
                    default:
                        $status = AvailabilityLog::UNAVAILABLE;
                        break;
                }
                AvailabilityLog::create([
                    "status" => $status,
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName,
                    "custom_status" => $agent->custom_status
                ]);
            }
        });
    }

    public function newCollection(array $models = [])
    {
        return new AgentCollection($models);
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
        return $this->belongsToMany('App\Models\Task', 'rules')->withPivot('priority');
    }

    public function assignments() {
        return $this->hasMany('App\Models\Assignment');
    }

    public function latestAvailability() {
        return $this->hasOne('App\Models\AvailabilityLog')->latest()->first();
    }

    public function getFullNameAttribute()
    {
        return $this->zendesk_custom_field_name != '-' ? "{$this->zendesk_custom_field_name} ({$this->zendesk_group_name}, {$this->zendesk_agent_name})" : "{$this->zendesk_group_name}/{$this->zendesk_agent_name}";
    }    
 
    public function getTest()
    {
        return "hallo";
    }    
}
