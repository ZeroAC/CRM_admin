<?php

namespace App\Library;

use Illuminate\Support\Facades\Redis;

class ApiSecurity
{

    /**
     * 通用接口
     * @param $request
     * @return bool|string
     * @author lc
     */
    public function common($request)
    {
        if ($request->all()) {
            $data = $request->all();
            $ckTime = $this->checkTime($data['time']);

            if (!$ckTime) return 'SN002';

            if (!isset($data['guid'])) return 'SN004';

            // 根据版本设计不同的验证
            switch ($data['version']) {
                case 1:
                    $temp = $this->checkCommon_v1($request);
                    break;
                default:
                    $temp = $this->checkCommon_v1($request);
                    break;
            }

            if ($temp) {
                return "SN200";
            }
            return "SN005";
        }
        return false;
    }


    /**
     * 非通用接口
     * @param $request
     * @return bool|string
     * @author lc
     */
    public function proprietary($request)
    {
        if ($request->all()) {
            $data = $request->all();
            $ckTime = $this->checkTime($data['time']);

            if (!$ckTime) return 'SN002';

            if (!isset($data['guid'])) return "SN004";

            // 根据版本设计不同的验证
            switch ($data['version']) {
                case 1:
                    $temp = $this->checkProprietary_v1($request);
                    break;
                default:
                    $temp = $this->checkProprietary_v1($request);
                    break;
            }

            if ($temp) {

                switch ($temp) {
                    case 'SN007':
                        return 'SN007';
                        break;
                    case 'SN008':
                        return 'SN008';
                        break;
                    case 'SN009':
                        return 'SN009';
                        break;
                    default:
                        return 'SN200';
                        break;
                }
            }
            return "SN005";
        }
        // No access! 没有添加签名验证
        return false;
    }

    /**
     * 时间验证
     * @param $time
     * @return bool|string
     * @author lc
     */
    public function checkTime($time)
    {

        $Time_difference = abs(time() - $time);
        if ($Time_difference > 30) {
            return false;
        }
        return true;
    }


    /**
     * 通用接口验证
     * @param $request
     * @return bool
     * @author lc
     */
    private function checkCommon_v1($request)
    {
        $data = $request->all();
        $path = '/' . $request->path();
        $time = $data['time'];
        $guid = '45dc325dhs5m';
        $param = $data['param'];
        $cryptToken = 'cinterViewAdmin888';

        $signature = md5($path . $time . $guid . $param . $cryptToken);

// app('log')->info($path .'---'. $time .'---'. $guid .'---'. $param .'---'. $cryptToken);
// app('log')->info($signature . '=====');

        if ($signature != $data['signatures']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 非通用接口验证
     * @param $request
     * @return bool
     * @author lc
     */
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
        // 获取用户信息
        $user = $this->user($guid);
        if (!$user) return 'SN007';  // 用户不存在
        // TOKEN 过期
        if(time() > $user['token_time']) {
            return 'SN009';
        }

        $token = $user['token'];

        $hashs = [
            [0, 4, 9, 15, 22, 28],
            [2, 8, 19, 25, 30, 31],
            [20, 25, 31, 3, 4, 8],
            [25, 31, 0, 9, 13, 17],
            [29, 2, 11, 17, 21, 26],
            [10, 15, 18, 29, 2, 3],
            [5, 10, 15, 17, 18, 22],
            [8, 20, 22, 27, 19, 25],
        ];
        $strs = substr($token, 1, 1);
        $strs .= substr($token, 4, 1);
        $strs .= substr($token, 7, 1);
        $code = hexdec($strs);
        $str1 = $code % 8;
        $arr = $hashs["$str1"];
        $m = null;
        foreach ($arr as $v) {
            $m .= substr($token, $v, 1);
        }

        $str = md5($path . $time . $guid . $param . $m);

// app('log')->info($path .'---'. $time .'---'. $guid .'---'. $param .'---'. $m);
// app('log')->info($signature . '=====');

        if ($signature == $str) {
            return 'SN200';
        } else {
            return false;
        }
    }

    /**
     * 根据guid 获取token
     *
     * @param $guid
     * @return bool|int
     * @author lc
     */
    public function user($guid)
    {
        // 拼接获取token的key
        $redisKey = config('redisKey')['tokenInfo'].$guid;
        // 获取缓存里的token
        $data = Redis::hGetall($redisKey);

        // 判断是否获取
        if($data){
            Redis::expire($redisKey, 3600*24*30); // 设置有效期30天
            return $data;
        }
        // 没有获取到重新获取 此处请调用获取token的服务
        $res = app('tokenService')->detail(['guid'=>$guid, 'type' => 2]);

        // app('log')->info('没有获取到重新获取');
        // app('log')->info($res);
                if ($res['status']) {
        // app('log')->info($res['status'].'???');
                    $array = (array)$res['msg'];
                    // 重新存入的redis中
                    Redis::hMset($redisKey,$array);
                    Redis::expire($redisKey, 3600*24*30);
                    // 返回
                    return $array;
                }
        // app('log')->info($res['status'].'返回错误');
                // 返回错误
        return false;
    }

}
