<?php

use App\Task;
use App\Agent;
// use Encore\Admin\Admin;
use App\Assignment;
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

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/groups', 'HomeController@groups');
    $router->get('/rules', 'HomeController@rules');
    $router->get('/logs', 'HomeController@logs');
});

Route::get('run', function() {
    App\Jobs\ProcessTask::dispatch(Task::find("view_360001440115-grouping_360000349636"));
    return redirect()->back();
});
Route::post('run', function() {
    App\Jobs\ProcessTask::dispatchNow(Task::find("view_360001440115-grouping_360000349636"));
    return response()->json();
});