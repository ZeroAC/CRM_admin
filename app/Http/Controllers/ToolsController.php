<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;


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
    
    public function getCaptcha($number)
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
        //获取验证码的答案
        $phrase = $builder->getPhrase();
        //将随机生成的验证码与前端传来的number绑定 存入redis
        //setex 命令为指定的 key 设置值及其过期时间。如果 key 已经存在， SETEX 命令将会替换旧的值
        app('redis')->setex('captcha:' . $number, 180, strtolower($phrase));
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $builder->output();
    }

}
