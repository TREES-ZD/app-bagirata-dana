<?php

namespace App;

use App\Scopes\AgentUserScope;
use App\Services\Zendesk\Ticket;
use Illuminate\Support\Collection;
use App\Collections\AgentCollection;
use App\Services\Agents\OrderTag;
use App\Services\Zendesk\TicketCollection;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\SortableTrait;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class Agent extends Model implements Sortable
{
    use Cachable;
    
    use SortableTrait;

    public const DEFAULT_VIEW = "DEFAULT_VIEW";
    
    public const DEFAULT_GROUP = "DEFAULT_GROUP";
    
    public const ASSIGNMENT = "ASSIGNMENT";

    public const RETRIED_ASSIGNMENT = "RETRIED_ASSIGNMENT";

    public const REASSIGNMENT = "REASSIGNMENT";

    public const UNASSIGNMENT = "UNASSIGNMENT";

    public const OBSERVED_UNASSIGNMENT = "OBSERVED_UNASSIGNMENT";

    public const AVAILABLE = true;
    public const UNAVAILABLE = false;

    public $assignedTasks;

    public $recentFailedTickets;

    public $preparedAssignments;

    /**
     * @var EloquentCollection| null
     */
    public $latestAssignmentsByViewId; 

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

                AvailabilityLog::create([
                    "status" => $agent->status == self::AVAILABLE ? AvailabilityLog::AVAILABLE : AvailabilityLog::UNAVAILABLE,
                    "agent_id" => $agent->id,
                    "agent_name" => $agent->fullName
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

    public function assignedTasks(): Collection
    {        
        return $this->assignedTasks ?: $this->rules()->get();
    }

    public function recentFailedTickets(): Collection
    {
        return $this->recentFailedTickets ?: $this->assignments()->where('response_status', 'FAILED')->where('type', 'ASSIGNMENT')->where('created_at', '>', now()->subMinutes(10))->get();
    }

    public function latestAssignmentOrder($orderTag): ?int
    {
        $tag = (new OrderTag())->parseTag($orderTag);
        $assignment = $this->latestAssignmentsByViewId ? $this->latestAssignmentsByViewId->get($tag->viewId) : Assignment::where('agent_id', $this->id)->where('zendesk_view_id', $tag->viewId)->latest()->first();
        
        return optional($assignment)->id;
    }

    public function assignedViewIds(): array
    {
        return $this->assignedTasks()->pluck('zendesk_view_id')->all();
    }

    public function prepareAssignment(Ticket $ticket): bool
    {
        $this->preparedAssignments = $this->preparedAssignments ?: new TicketCollection();

        if ($this->isEligible($ticket)) {
            $this->preparedAssignments->push($ticket);
            return true;
        }
        
        return false;
    }

    public function getPreparedAssignments(): TicketCollection
    {
        return $this->preparedAssignments;
    }

    public function zendeskGroupId(): ?string
    {
        return $this->zendesk_group_id;
    }

    //[default_view, default_group, view:view1-group:123456, view:view2-group:123456, view:view1-default_group, view:view2-default_group, default_view:group:123456]
    public function getOrderIdentifierTags(): Collection
    {
        return collect($this->assignedViewIds())->merge([Agent::DEFAULT_VIEW])->crossJoin(collect($this->zendeskGroupId())->merge(Agent::DEFAULT_GROUP))->map(function($pair) {
            return new OrderTag($pair[0], $pair[1]);
        });

        // return collect([Agent::DEFAULT_VIEW, AGENT::DEFAULT_GROUP]);
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

    private function isEligible(Ticket $ticket)
    {
        return $this->getOrderIdentifierTags()->map->__toString()->contains((string) $ticket->getOrderIdentifier());
    }
}
