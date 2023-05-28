<?php

namespace App\Admin\Controllers;

use App\Models\Agent;
use App\Models\Group;
use App\Models\Task;
use Carbon\Carbon;
use App\Models\Assignment;
use Encore\Admin\Grid;
use App\Models\AvailabilityLog;
use Encore\Admin\Grid\Column;
use Encore\Admin\Grid\Displayers\Table;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Facades\Admin;
use Jxlwqq\DataTable\DataTable;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Repositories\AgentRepository;
use App\Repositories\AssignmentRepository;
use Illuminate\Support\Facades\Redis;
use Str;

class HomeController extends Controller
{
    public function index(Content $content, Request $request, AgentRepository $agentRepo, AssignmentRepository $assignmentRepo)
    {
        if ($request->current == "on") {
            $agentsWithAssignmentCount = Agent::disableCache()->get()->map(function($agent) {
                return [
                    'full_name' => $agent->full_name,
                    'assignment_count' => count(Redis::smembers('agent:'.$agent->id.':assignedTickets'))
                ];   
            })->sortByDesc('assignment_count')->take(20);
        } else {
            $agentsWithAssignmentCount = $agentRepo->getWithAssignmentsCount($request->from, $request->to, $request->availability);
        }
        
        $totalAssignmentChartTitle = $this->totalAssignmentChartTitle($request);
        return $content
            ->title('Home')
            ->description('Dashboard')
            ->row(function (Row $row) use ($assignmentRepo, $agentsWithAssignmentCount, $totalAssignmentChartTitle) {
                $full_names = $agentsWithAssignmentCount->pluck('full_name');
                $assignment_counts = $agentsWithAssignmentCount->pluck('assignment_count');
                
                // Availability Logs table
                $availabilityLogs = AvailabilityLog::latest("id")->limit(10)->get(["created_at", "custom_status", "agent_name"]);
                // Latest Assignments table
                $latestAssignments = Assignment::latest("id")->limit(10)->get(["created_at", "agent_name", "zendesk_view_id", "zendesk_ticket_id", "zendesk_ticket_subject", "type"]);
                
                // $taskEnabledTotalHtml =  $totalEnabledTasksCount ? sprintf('<a href=%s>%d</a> %s', route('rules.index', '/backend/tasks?task_status=1', $totalEnabledTasksCount, $notAssignedTasks ? "<a href='".route('rules.index', ['_scope_' => 'unassigned_active_tasks'])."' style=\"color: #bc4727\">(".$notAssignedTasks->count()." have no available assignee) </a>" : '')) : "None";
                $taskEnabledTotalHtml =  sprintf('<a href=%s>%d</a>', '/backend/rules?task_status=1', Task::where('enabled', true)->count());
                // $availableAgentsTotalHtml =  sprintf('<a href=%s>%d</a>', '/backend/agents?status=1', Agent::disableCache()->where('status', Agent::AVAILABLE)->count());
                $availableAgentsTotalHtml =  sprintf('<a href=%s>🟢 %d</a>', '/backend/agents?custom_status=AVAILABLE', Agent::disableCache()->where('custom_status', Agent::CUSTOM_STATUS_AVAILABLE)->count());
                // $unavailableAgentsTotalHtml =  sprintf('<a href=%s>%d</a>', '/backend/agents?status=0', Agent::disableCache()->where('status', Agent::UNAVAILABLE)->count());
                $unavailableAgentsTotalHtml =  sprintf('<a href=%s>🔴 %d</a>', '/backend/agents?custom_status=UNAVAILABLE', Agent::disableCache()->where('custom_status', Agent::CUSTOM_STATUS_UNAVAILABLE)->count());
                $awayAgentsTotalHtml =  sprintf('<a href=%s>🕘 %d</a>', '/backend/agents?custom_status=AWAY', Agent::disableCache()->where('custom_status', Agent::CUSTOM_STATUS_AVAILABLE)->count());


                if (str(url()->full())->contains('jago')) {
                    $totalAssignmentsByDateRange = $assignmentRepo->getTotalAssignmentsByDateRange();
                    $totalFailedAssignmentsByDateRange = $assignmentRepo->getTotalFailedAssignmentsByDateRange();

                    $row->column(3, new Box("Today", view('roundrobin.dashboard.tile', ['data' => $totalAssignmentsByDateRange['today'], 'failed_data' => $totalFailedAssignmentsByDateRange['today']])));
                    $row->column(3, new Box("Yesterday", view('roundrobin.dashboard.tile', ['data' => $totalAssignmentsByDateRange['yesterday'],'failed_data' => $totalFailedAssignmentsByDateRange['yesterday']])));
                    $row->column(3, new Box("Last Week", view('roundrobin.dashboard.tile', ['data' => $totalAssignmentsByDateRange['in_a_week'],'failed_data' => $totalFailedAssignmentsByDateRange['in_a_week']])));
                    $row->column(3, new Box("Last Month", view('roundrobin.dashboard.tile', ['data' => $totalAssignmentsByDateRange['in_a_month'],'failed_data' => $totalFailedAssignmentsByDateRange['in_a_month']])));
                }

                $row->column(8, new Box("Agent(s) by number of assignments", view('roundrobin.dashboard.agentTotalAssignments', compact('full_names', 'assignment_counts', 'totalAssignmentChartTitle'))));
                $row->column(4, new Box("Agent(s) total availability", $availableAgentsTotalHtml . '<br>' . $unavailableAgentsTotalHtml . '<br>' . $awayAgentsTotalHtml?: "None"));
                // $row->column(4, new Box("Agent(s) unavailable", $unavailableAgentsTotalHtml ?: "None"));
                $row->column(4, new Box("Task(s) monitored", $taskEnabledTotalHtml));
                $row->column(4, new Box("Availability logs", view('roundrobin.dashboard.availabilityLogs', compact('availabilityLogs'))));
                $row->column(12, new Box("Latest assignments", view('roundrobin.dashboard.latestAssignments', compact('latestAssignments'))));                
            });
    }


    public function groups(Content $content) {
        return $content
            ->title('Groups')
            ->row(function (Row $row) {

                            foreach (Group::all() as $group) {
                                $row->column(6, new Grid(new Agent, function(Grid $grid) use ($group) {
                                    // $grid->paginate(5);
                                    $grid->model()->where('zendesk_group_id', $group->group_id);

                                    $grid->setTitle($group->group_name);

                                    $grid->disableColumnSelector();
                                    $grid->disableExport();
                                    // $grid->disableCreateButton();
                                    $grid->disableFilter();
                                    $grid->batchActions(function ($batch) {
                                        $batch->disableDelete();
                                    });
                                    $grid->disableCreateButton();
                                    $grid->disableActions();
                                    $grid->disableBatchActions();
                                    
                                    $grid->fullName();
                                }));
                            }
            });
    }    

    public function assignment_logs(Content $content) {
        $grid = new Grid(new Assignment());
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableFilter();

        $grid->model()->orderBy('id', 'desc');

        // $grid->filter(function($filter) {
        //     // $filter->disableIdFilter();
            
        //     // $filter->between('created_at', 'Created at')->datetime();
        //     // $filter->equal('zendesk_view_id', 'View ID');
        //     // $filter->equal('zendesk_ticket_id', 'Ticket ID');
        //     // $filter->ilike('agent_name', 'Agent Name');
        //     // $filter->equal('type', 'Type')->select([
        //     //     'ASSIGNMENT' => 'Assignment',
        //     //     'UNASSIGNMENT' => 'Unassignment'
        //     // ]);
        //     // $filter->equal('response_status', 'Status')->multipleSelect([
        //     //     '200' => 'Success',
        //     //     'FAILED' => 'Failed',
        //     //     'PENDING' => 'Pending'
        //     // ]);  
        //     // $filter->in('agent_id', [35]);
        // });  

        $grid->column("created_at", 'Assigned at')->filter('range', 'datetime');
        $grid->column("agent_name")->filter('like');
        $grid->column('zendesk_view_id', 'View ID')->display(function () {
            $subdomain = config('zendesk-laravel.subdomain');
            return is_numeric($this->zendesk_view_id) ? sprintf("<a href=\"https://%s.zendesk.com/agent/filters/%s\">%s</a>", $subdomain, $this->zendesk_view_id, $this->zendesk_view_id) : '';
        })->filter();
        $grid->column('zendesk_ticket_id', 'Ticket ID')->display(function () {
            $subdomain = config('zendesk-laravel.subdomain');
            $html = sprintf("<a href=\"https://%s.zendesk.com/agent/tickets/%s\">%s</a>", $subdomain, $this->zendesk_ticket_id, $this->zendesk_ticket_id);
            return $html;
            
        })->filter();
        $grid->column("zendesk_ticket_subject")->filter('like');
        $grid->column("type")->filter([
            'ASSIGNMENT' => 'Assignment',
            'UNASSIGNMENT' => 'Unassignment'
        ]);   
        // $grid->column("response_status", "Status")->bool(['200' => true, 'FAILED' => false]);    
        $grid->column('response_status', 'Status')->display(function ($value, Column $column) {
            if ($value == '200') return '<i class="fa fa-check text-green"></i>';
            if ($value == 'PENDING') return '<i class="fa fa-circle text-yellow"></i>';
            return '<i class="fa fa-times text-red"></i> ' . $this->response_details;
            // return view('roundrobin.components.assignment-modal', ['response_details' => $this->response_details, 'key' => $this->getKey(), 'value' => $value, 'name' => $this->getKey()]);
            
        })->filter([
            '200' => 'Success',
            'FAILED' => 'Failed',
            'PENDING' => 'Pending'
        ]);
        return $content->body($grid);
        
    }
    
    public function availability_logs(Content $content) {
        $grid = new Grid(new AvailabilityLog());
        $grid->disableColumnSelector();
        $grid->disableRowSelector();
        $grid->disableCreateButton();
        // $grid->disableFilter();
        $grid->disableActions();


        if (str(url()->full())->contains('jago')) {
            $grid->model()
            ->select('a.id','a.agent_name', 'a.status', 'a.created_at', 'a.custom_status', 'b.created_at AS previous_created_at')
            ->from('availability_logs AS a')
            ->leftJoin('availability_logs AS b', function ($join) {
                $join->on('a.agent_name', '=', 'b.agent_name')
                    ->whereColumn('b.created_at', '<', 'a.created_at')
                    ->whereRaw('b.created_at = (
                        SELECT MAX(created_at)
                        FROM availability_logs
                        WHERE agent_name = a.agent_name
                        AND created_at < a.created_at
                    )');
            });
        } else {
            $grid->model()
            ->select('a.id','a.agent_name', 'a.status', 'a.created_at', 'a.custom_status')
            ->from('availability_logs AS a');
        }

        $grid->filter(function(\Encore\Admin\Grid\Filter $filter) {
            $filter->disableIdFilter();

            // $filter->between('created_at', 'Time')->datetime();
            // $filter->equal('status', 'Status')->select([
            //     'Available' => 'Available',
            //     'Unavailable' => 'Unavailable'
            // ]); 
            $filter->column(12, function($filter) {
                $filter->where(function ($query) {
                    $from = request('from');
                    $query->where('a.created_at', '>=', Carbon::parse($from));
                }, 'From', 'from')->datetime();
                $filter->where(function ($query) {
                    $to = request('to');
                    $query->where('a.created_at', '<=', Carbon::parse($to));
                }, 'To', 'to')->datetime();
            });
            $filter->column(12, function($filter) {
                $filter->where(function ($query) {
                    $query->where('a.custom_status', request('custom_status'));
                }, 'Status', 'custom_status')->select([
                    Agent::CUSTOM_STATUS_UNAVAILABLE => '🔴 Unavailable',
                    Agent::CUSTOM_STATUS_AVAILABLE => '🟢 Available',
                    Agent::CUSTOM_STATUS_AWAY => '🕘 Away' 
                ]);
                $filter->where(function ($query) {
                    $query->where('a.agent_name', 'ILIKE', "%".request('agent_name')."%");
                }, 'Agent Name', 'agent_name');
            });
            

            // $filter->ilike('agent_name', 'Agent Name');
            // $filter->getModel();
            // $filter->in('agent_id', [35]);
        });  

        // ->orderBy('a.created_at');

        
        // dd($grid->model()->getQueryBuilder()->count());
        // $availabilityLogsDictionary = $grid->model()->getQueryBuilder()->get()->groupBy('agent_name')
        //                                 ->map(function ($agentRecords) {
        //                                     $previousCreatedAt = null;
                                    
        //                                     return $agentRecords->map(function ($record) use (&$previousCreatedAt) {
        //                                         $createdAt = $record->created_at;
        //                                         $status = $record->status;
                                    
        //                                         $timeGap = null;
        //                                         //$status === 'Unavailable' && $previousCreatedAt
        //                                         if ($previousCreatedAt) {
        //                                             $timeGap = Carbon::parse($createdAt)->diffInSeconds($previousCreatedAt);
        //                                         }
                                    
        //                                         $previousCreatedAt = $createdAt;
                                    
        //                                         return array_merge($record->toArray(), ['time_gap' => $timeGap]);
        //                                     });
        //                                 });
        // dd($availabilityLogsDictionary);

        $grid->model()->orderBy('id', 'desc');
        $grid->created_at("Time");
        // $grid->status("Status");
        $grid->custom_status("Availability")->display(fn($value) => $value ?? strtoupper($this->status));
        $grid->agent_name("Agent Name");

        if (str(url()->full())->contains('jago')) {
            $grid->column('previous_created_at', 'Time Gap')->display(function() {
                $timeGap = $this->previous_created_at ? Carbon::parse($this->created_at)->diffForHumans($this->previous_created_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE, false) : 'None';
    
                return $timeGap;
            });
        }


        return $content->body($grid);
    }        

    private function totalAssignmentChartTitle($request)
    {
        $from = $request->from ?: "today";
        $to = $request->to ?: "now";
        $time = "from $from to $to";

        $subject = $request->availability == "available" || $request->availability == "unavailable" ? "Current $request->availability agent(s)" : "All agents";
        
        return "$subject by total assignment(s) $time";
    }
}
