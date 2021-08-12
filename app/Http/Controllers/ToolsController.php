<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Http\Request;

class ToolsController extends Controller
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
    
    /**
     * 在后台生成随机的验证码 并与前端传来的number绑定存入redis 用于快速验证
     *
     * @param [string] $number
     * @return 验证码图片
     * @Author jy
     * @DateTime 2021-08-12 15:03:26
     */
    public function getCaptcha(string $number)
    {
        $phrase = new PhraseBuilder;
        // 设置验证码位数
        $code = $phrase->build(6);
        // 生成验证码图片的Builder对象，配置相应属性
        $builder = new CaptchaBuilder($code, $phrase);
        //设置验证码的背景
        $builder->setBackgroundColor(212, 255, 155);
        //设置验证码干扰
        $builder->setMaxBehindLines(4);
        $builder->setMaxFrontLines(4);
        //设置验证码大小
        $builder->build($width = 120, $height = 47, $font = null);
        //获取验证码的答案 答案不区分大小写
        $phrase = strtolower($builder->getPhrase());
        //将随机生成的验证码与前端传来的number绑定 存入redis
        //setex 命令为指定的 key 设置值及其过期时间。如果 key 已经存在， SETEX 命令将会替换旧的值
        app('redis')->setex('captcha:' . $number, 180, $phrase);
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $builder->output();
    }

    /**
     * 验证前端传来的验证码值是否正确
     *
     * @param [string] $number 在redis中查找的键
     * @param [string] $captcha 待验证的值
     * @return ['status' =>'xxx', 'msg' => 'xxx']
     * @Author jy
     * @DateTime 2021-08-12 15:36:51
     */
    public function verifyCaptcha(string $number, string $captcha)
    {
        $key = 'captcha:' . $number;//获取验证码在redis中的key
        
        //验证码是否过期验证
        if(!app('redis')->exists($key)) return ['status' => false, 'msg' => '验证码过期'];
        
        $ans = app('redis')->get($key);//标答
        $x = strtolower($captcha);//待验证的值 不区分大小写 

        //验证值是否正确
        if ($x == $ans) return ['status' => true, 'msg' => '验证码正确'];
        return ['status' => false, 'msg' => '输入错误'];
    }

}
