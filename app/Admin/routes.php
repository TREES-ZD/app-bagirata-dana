<?php

use Illuminate\Routing\Router;
use Illuminate\Http\Request;
use App\Agent;
use App\Assignment;
use Zendesk\API\HttpClient as ZendeskAPI;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('admin.home');

    $router->get('/agents', 'AgentController@index');    
    $router->get('/agents/create', 'AgentController@create');
    $router->post('/agents', 'AgentController@store');
    $router->get('/agents/{id}', 'AgentController@show');
    $router->get('/agents/{id}/edit', 'AgentController@edit');
    $router->put('/agents/{id}', 'AgentController@update');
    $router->delete('/agents/{id}', 'AgentController@destroy');

    $router->get('/tasks', 'TaskController@index');

    $router->get('/schedules', 'HomeController@schedules');
    $router->get('/jobs', 'HomeController@jobs');
    $router->get('/rules', 'HomeController@rules');
    $router->get('/logs', 'HomeController@logs');
});

Route::post('run', function() {
    App\Jobs\ProcessTask::dispatchNow("123");
    return response()->json();
});