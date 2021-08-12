<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});
//生成验证码
$router->get('/captcha/{number}', 'ToolsController@getCaptcha');
//验证验证码
$router->get('api/admin/login', 'ToolsController@verifyCaptcha');
$router->post('api/admin/login', 'ToolsController@verifyCaptcha');

$router->group(['prefix' => 'api'], function () use ($router) {

    //管理员管理相关路由
    $router->group(['prefix' => 'admin','namespace' => 'Admin'], function () use ($router) {
        // $router->post('login', 'UserController@login');
        // $router->get('login', 'UserController@login');
    });

    //客户管理相关路由
    $router->group(['prefix' => 'customer'], function () use ($router) {
        $router->post('users', function () {
        });
    });
});
