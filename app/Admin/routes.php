<?php

use App\Task;
use App\Agent;
// use Encore\Admin\Admin;
use App\Assignment;
use DebugBar\DebugBar;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use App\Services\ZendeskService;
use Illuminate\Support\Facades\DB;
use Zendesk\API\HttpClient as ZendeskAPI;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {
    $router->resource('auth/users', 'UserController')->names('admin.auth.users');

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/agents', 'AgentController@index');    
    $router->get('/agents/create', 'AgentController@create');
    $router->post('/agents', 'AgentController@store');
    $router->get('/agents/sync', 'AgentController@sync');
    $router->get('/agents/{id}', 'AgentController@show');
    $router->get('/agents/{id}/edit', 'AgentController@edit');
    $router->put('/agents/{id}', 'AgentController@update');
    $router->delete('/agents/{id}', 'AgentController@destroy');
    $router->post("/agents/sync", function() {

        return response()->json(["message" => 'good']);
    });

    $router->get('/tasks', 'TaskController@index');
    $router->put('/tasks/{id}', 'TaskController@update');
    $router->get('/tasks/sync', 'TaskController@sync');

    $router->get('/rules', 'RuleController@index');
    $router->put('/rules/{id}', 'RuleController@update');

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/groups', 'HomeController@groups');
    $router->get('/assignment_logs', 'HomeController@assignment_logs');
    $router->get('/availability_logs', 'HomeController@availability_logs');
});

Route::get('run', function(Request $request) {
    if ($request->has('_pjax')) {
        App\Jobs\Task\ProcessTask::dispatch(Task::where('zendesk_view_id', $request->view_id)->first())->onQueue('assignment');    
    }
    return redirect()->back();
});
Route::post('run', function() {
    App\Jobs\ProcessTask::dispatchNow(Task::find(1));
    return response()->json();
});
Route::post('unassign', function(Request $request) {
    App\Jobs\Agent\UnassignTickets::dispatchNow(Agent::find($request->agent_id))->onQueue('unassignment');
    return response()->json();
});
Route::post('syncAgents', function(Request $request) {
    App\Jobs\SyncAgents::dispatch();
    return response()->json();
});
Route::get('refresh', function(Request $request) {
    app(ZendeskService::class)->refresh();
    return redirect()->back();
});
Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
Route::get('sleep', function(Request $request) {
    sleep(20);
    return response()->json();
});
Route::get('long_query', function(Request $request) {
    DB::connection()->select(DB::raw("SELECT pg_sleep(60)"));
    return response()->json();
});
Route::get('heavy', function(Request $request) {
    $a = 0;
    for($i = 0; $i < 1000000000; $i++) {
        $a += $i;
   }
    return response()->json();
});