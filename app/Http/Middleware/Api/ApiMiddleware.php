<?php

namespace App\Http\Middleware\Api;
use Illuminate\Http\Request;
use App\Library\Tools;
use Closure;

class ApiMiddleware
{
    private static $verify;

    public function __construct(ApiSecurity $verify)
    {
        self::$verify = $verify;
    }

    //安全 验签
    private function verify(Request $request)
    {
        $commonPath = [//走通用接口的url(即无需登录就能访问的)
            'api/admin/login'
        ];

        //选择采取哪个验证
        if(in_array($request->path(), $commonPath)){
            return  self::$verify->common($request);
        }else {
            return self::$verify->proprietary($request);
        }
        return false;
    }

    public function handle(Request $request, Closure $next)
    {
        $time = time();

        //获得中间件ApiSecurity的验证结果状态码
        $passRes = $this->verify($request);

        //根据状态码返回约定好的规范响应
        switch($passRes)
        {
            case "SN200"://OK
                return $next($request);
                break;
            case "SN001"://服务器内部错误
                return Tools::serverRes('SN001','','Server internal error!'); 
                break;
            case "SN002"://时间异常
                return Tools::serverRes('SN002','','Request timeout!'); 
                break;
            case "SN003"://版本号异常
                return Tools::serverRes('SN003','','Version number exception!');
                break;
            case "SN004"://全局用户id不能为空
                return Tools::serverRes('SN004','','Global user ID can not be null!');
                break;
            case "SN005"://签名错误
                return Tools::serverRes('SN005','','Signature error!');
                break;
            case "SN007"://服务器维护状态
                return Tools::serverRes('SN007','','Server maintenance status');
                break;
            case "SN008"://当前用户token不存在 也即该用户不存在 后台中token是非空的 注册时就有默认的token生成
                return Tools::serverRes('SN008','','The current user token does not exist');
                break;
            case "SN009"://当前用户token已过期
                return Tools::serverRes('SN009','','The current user token has expired');
                break;
            case "SN0010"://当前用户token错误
                return Tools::serverRes('SN0010','','Current user token error');
                break;
            case "SN0011"://当前用户被封禁
                return Tools::serverRes('SN0011','','The user has been banned');
                break;
            case "SN012"://当前设备被封禁
                return Tools::serverRes('SN0012','','No access!');
                break;
            default:
                return Tools::serverRes('SN006','','Current user token error');
        }
    }

}
