<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Library\Tools;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

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

$router->group(['prefix' => 'api','middleware' => 'auth'], function () use ($router) {
    //管理员管理相关路由
    $router->group(['prefix' => 'admin','namespace' => 'Admin'], function () use ($router) {
        $router->post('login', 'UserController@login');
        $router->get('info','UserController@info');
    });

    //客户管理相关路由
    $router->group(['prefix' => 'customer'], function () use ($router) {
        $router->post('users', function () {
        });
    });
});
$router->get('add',function(){//添加超级用户
    $guid = Tools::getUuid();//获取32位字符串guid
    $user_name = 'jy';
    $salt = md5(substr($user_name,0,3));//取前三位为盐
    $password = md5('123456'.$salt);//密码加盐后存入
    $status = 1;
    $token = Tools::getUuid();//生成该用户的默认token值
    $token_time = time()+30*24*3600;//数据库中的token过期时间为30天
    $add_time = time();//用户添加时间
    $last_time = time();//用户最后登录时间
    $last_ip = '127.0.0.1';//用户最后登录的ip
    $data = compact('guid','user_name','password','salt','status','token','token_time','add_time','last_time','last_ip');
    $res = app('db')->table('data_admin_login')->insert($data);
    return response()->json($res ? 'success':'fail');
});
$router->get('test',function(){//数据库测试
    $tb = app('db')->table('data_admin_login');
    $guid = '0c839890fbe511ebb5e70242c0a85002';
    $token= $tb->select('token','token_time')->where('guid',$guid)->first();
    dd($token);
});
