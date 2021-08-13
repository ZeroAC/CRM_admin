<?php

namespace App\Http\Middleware\Api;
use Illuminate\Http\Request;
/**
 * 签名接口专用返回值code码 以SN开头
 */
class ApiSecurity
{

    //通用接口签名验证
    public function common(Request $request)
    {
        $data = $request->all();
        if(empty($data)) return false;//数据为空直接返回错误

        if (!$this->checkTime($data['time'])) return 'SN002';//时间异常

        if (!isset($data['guid'])) return 'SN004';//guid不能为空

        $res = null;//通用签名验证结果
        // 根据版本设计不同的验证 当前只有一个版本
        switch ($data['version']) {
            case 1:
                $res = $this->checkCommon_v1($request);
                break;
            default:
                $res = $this->checkCommon_v1($request);
                break;
        }
        if ($res) return "SN200";
        return "SN005";//签名错误
    }

    //业务接口签名验证
    public function proprietary($request)
    {
        $data = $request->all();
        if(empty($data)) return false;//数据为空直接返回错误

        if (!$this->checkTime($data['time'])) return 'SN002';//时间异常

        if (!isset($data['guid'])) return 'SN004';//guid不能为空

        $res = null;//业务签名验证结果
        // 根据版本设计不同的验证 当前只有一个版本
        switch ($data['version']) {
            case 1:
                $res = $this->checkProprietary_v1($request);
                break;
            default:
                $res = $this->checkProprietary_v1($request);
                break;
        }
        return $res;
    }

    public function checkTime($time)
    {

        $Time_difference = abs(time() - $time);
        if ($Time_difference > 30) {
            return false;
        }
        return true;
    }


   //通用接口验证
    private function checkCommon_v1($request)
    {
        $data = $request->all();
        $path = '/' . $request->path();
        $time = $data['time'];
        $guid = 'CRM2021080808';
        $param = $data['param'];
        $token = 'CRMPublicToken2021';

        $signature = md5($guid . $param . $time . $token . $path);
        // var_dump($signature);

        return $signature == $data['signatures'];
    }

    //业务接口验证
    private function checkProprietary_v1($request)
    {
        $data = $request->all();

        $path = '/' . $request->path();
        // 获取参数
        $param = $data['param'];
        // 获取用户ID
        $guid = $data['guid'];
        // 获取签名
        $signature = $data['signatures'];
        // 获取提交时间
        $time = $data['time'];
        // 获取用户token 并更新缓存中的过期时间
        $token = $this->user($guid);
        if ($token=='SN008') return 'SN008';  // 该用户不存在 
        else if($token=='SN009') return 'SN009';//token过期 重新登录
        // var_dump('token: ', $token);
        //token加密过程
        $cryptToken = null;//加密后的值
        $hashs = [
            [0, 6, 9, 15, 22, 28],
            [2, 8, 17, 25, 30, 31],
            [20, 28, 31, 3, 4, 8],
            [25, 31, 4, 9, 13, 17],
            [29, 2, 11, 27, 21, 26],
            [10, 15, 18, 21, 2, 3],
            [5, 10, 15, 17, 11, 22],
            [8, 20, 22, 27, 19, 27]
        ];
        $strs = substr($token, 1, 1) . substr($token, 5, 1) . substr($token, 6, 1);
        $code = hexdec($strs);
        $str1 = $code % 8;
        $arr = $hashs["$str1"];
        foreach ($arr as $v) $cryptToken .= substr($token, $v, 1);

        //用与前端一致的签名生成算法  用来校验前端的签名是否正确
        $ansSignature = md5($guid . $param . $time . $cryptToken . $path);
        // var_dump($ansSignature);
        //若签名一致 则通过验证
        if ($signature == $ansSignature) {
            return 'SN200';
        } else {
            return false;
        }
    }

    //根据guid查找其token
    public function user($guid)
    {
        // 获取缓存中的token
        $key = 'guid:' . $guid;
        
        // 该guid在缓存内是存在的则直接返回
        if(app('redis')->exists($key)){
            app('redis')->expire($key,7*24*3600);//更新过期时间为七天
            return app('redis')->get($key);
        }
        //缓存中不存在 则去数据库中获取
        $data = app('db')->table('data_admin_login')
                ->select('token','token_time')->where('guid',$guid)->first();
        if(!$data->token) return 'SN008';//数据库中无该guid,则查找失败
        if($data->token_time < time()) return 'SN009';//token已过期

        //此时则查找成功 将其存入缓存再返回即可
        app('redis')->setex($key, 7*24*3600, $data->token);
        return $data->token;//查找成功
    }
}
