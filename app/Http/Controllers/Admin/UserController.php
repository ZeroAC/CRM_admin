<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ToolsController;
use App\Library\Tools;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    //用户登录
    public function login(Request $request)
    {
        $data = $request->all();

        //参数 账号 密码 均不能为空
        if(!isset($data['param'])) return Tools::serverRes(400,'','parameter cannot be empty');
        $param = json_decode($data['param'], true);
        if(!isset($param['user_name'])) return Tools::serverRes(400,'','username can not be empty');
        $userName = $param['user_name'];
        if(!isset($param['password'])) return Tools::serverRes(400,'','password can not be blank');
        $password = $param['password'];
        
        //验证验证码
        $resCaptcha = ToolsController::verifyCaptcha($param['number'], $param['captcha']);
        if (!$resCaptcha['status']) return Tools::serverRes(400,'',$resCaptcha['msg']); //验证码验证失败

        //验证码正确 开始验证账号和密码
        $user = app('db')->table('data_admin_login')
            ->where('user_name', $userName)
            ->select('user_name', 'password', 'salt', 'guid', 'status', 'add_time')->first();
        //账号不存在      密码错误
        if (!$user || md5($password . $user->salt) != $user->password) {
            return Tools::serverRes(400, '', 'Account number does not exist or has a password error');
        }

        //账号被封
        if ($user->status != 1)  return Tools::serverRes(400, '', 'The account has been sealed. Please contact your administrator');

        //账号、密码、状态、均正确 下发token 
        $token = Tools::getUuid(); // 生产token
        $token_time = time() + 30 * 24 * 3600; //初始化token过期时间
        $last_time = time(); //更新最后登录时间
        $last_ip = $request->getClientIp(); //最后登录的ip

        //更新该用户信息
        app('db')->table('data_admin_login')
            ->where('guid', $user->guid)
            ->update(compact('token', 'token_time', 'last_time', 'last_ip'));

        //token存入缓存中
        app('redis')->setex('token:' . $user->guid, 7 * 24 * 3600, $token);

        $resData = [ //登录验证成功 返回数据
            'guid' => $user->guid,
            'user_name' => $userName,
            'password' => $user->password,
            'status' => 1,
            'token' => $token,
            "add_time" => $user->add_time,
            'token_time' => $token_time,
            'last_ip' => $last_ip,
            'last_time' => $last_time
        ];
        return Tools::serverRes(200, $resData, 'ok');
    }
}
