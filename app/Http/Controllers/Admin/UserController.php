<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ToolsController;
use App\Library\BaseDB;
use App\Library\Tools;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use BaseDB;
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
        $this->table = 'data_admin_login';//切换表为管理员登录表
        $user = $this->getFirst(['user_name', $userName]);
        //账号不存在      密码错误
        if (!$user || md5($password . $user->salt) != $user->password) {
            return Tools::serverRes(400, '', 'Account number does not exist or has a password error');
        }

        //账号被封
        if ($user->status != 1)  return Tools::serverRes(400, '', 'The account has been sealed. Please contact your administrator');

        //账号、密码、状态、均正确 下发token 
        $token = Tools::getUuid(); // 生产token 要转为字符串 不然存入后为对象
        $token_time = time() + 30 * 24 * 3600; //初始化token过期时间
        $last_time = time(); //更新最后登录时间
        $last_ip = $request->getClientIp(); //最后登录的ip

        //更新该用户信息
        $this->update(['guid',$user->guid],compact('token', 'token_time', 'last_time', 'last_ip'));
        app('redis')->setex('guid:' . $user->guid, 7*24*3600, $token);
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
    //用户详情
    public function info(Request $request)
    {
        $data = $request->all();
        $res = $this->getFirst(['admin_guid',$data['guid']]);
        $data = [
            'admin_guid' => $res->admin_guid,
            'nick_name' => $res->nick_name,
            'phone' => $res->phone,
            'avatar' => 'https://www.blog8090.com/content/images/2019/12/users.png'
        ];
        return Tools::serverRes('200',$data,'this is info');
    }

}
