<?php

namespace App\Http\Middleware\Api;
use App\Library\ApiSecurity;
use Closure;

class ApiMiddleware
{
    private static $verify;

    public function __construct(ApiSecurity $verify)
    {
        self::$verify = $verify;
    }

    private function verify($request)
    {
        $commonPath = [
            'api/admin/login',
            'api/admin/sendSMS',
        ];

        // 通用接口
        if(in_array($request->path(), $commonPath)){
            return  self::$verify->common($request);
        }else {
            return self::$verify->proprietary($request);

        }
        return false;
    }



    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $time = time();
        switch($res = $this->verify($request))
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
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN010','ResultData'=>'Permission denied']);
                break;
            default:
                return response()->json(['serverTime'=>$time,'ServerNo'=>'SN006','ResultData'=>'No access!']);
        }

    }

}
