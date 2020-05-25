<?php

use App\Task;
use App\Agent;
// use Encore\Admin\Admin;
use App\Assignment;
use DebugBar\DebugBar;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
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

    $router->get('/rules', 'RuleController@index');
    $router->put('/rules/{id}', 'RuleController@update');

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/groups', 'HomeController@groups');
    $router->get('/logs', 'HomeController@logs');
});

Route::get('run', function(Request $request) {
    if ($request->has('_pjax')) {
        App\Jobs\ProcessTask::dispatch(Task::find(1));    
    }
    return redirect()->back();
});
Route::post('run', function() {
    App\Jobs\ProcessTask::dispatchNow(Task::find(1));
    return response()->json();
});
Route::post('unassign', function(Request $request) {
    App\Jobs\UnassignTickets::dispatchNow(Agent::find($request->agent_id));
    return response()->json();
});
Route::post('syncAgents', function(Request $request) {
    App\Jobs\SyncAgents::dispatchNow();
    return response()->json();
});