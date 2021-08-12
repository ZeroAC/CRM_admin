<?php

namespace App\Http\Middleware\Api;

use Closure;

class ApiMiddleware
{
    private static $verify;

    public function __construct(ApiSecurity $verify)
    {
        self::$verify = $verify;
    }

    //安全 验签
    private function verify($request)
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

    public function handle($request, Closure $next)
    {
        $time = time();

        //获得中间件ApiSecurity的验证结果状态码
        $passRes = $this->verify($request);

        //根据状态码返回约定好的规范响应
        switch($passRes)
        {
            case "SN200":
                return $next($request);
                break;
            case "SN001":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN001','ResultData'=>'Server internal error!']);
                break;
            case "SN002":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN002','ResultData'=>'Request timeout!']);
                break;
            case "SN003":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN003','ResultData'=>'Version number exception!']);
                break;
            case "SN004":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN004','ResultData'=>'Global user ID can not be null!']);
                break;
            case "SN005":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN005','ResultData'=>'Signature error!']);
                break;
            case "SN007":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN007','ResultData'=>'user not!']);
                break;
            case "SN008":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN008','ResultData'=>'Other devices login!']);
                break;
            case "SN009":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN009','ResultData'=>'token time out!']);
                break;
            case "SN010":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN010','ResultData'=>'token error!']);
                break;
            case "SN011":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN011','ResultData'=>'The user has been banned']);
                break;
            case "SN012":
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN012','ResultData'=>'The device has been banned']);
                break;
            default:
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN006','ResultData'=>'No access!']);
        }

    }

}
