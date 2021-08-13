<?php

namespace App\Library;

use Illuminate\Http\Request;
use  Illuminate\Support\Facades\Validator;

//表单验证
class TableValidate
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
    //添加管理员时的表单验证
    public static function addAdmin($data)
    {
        $validator = Validator::make($data,[
            'user_name'=>'required|min:2|max:16',
            'nick_name'=>'required|min:2|max:16',
            //密码(以字母开头，长度在6~18之间，只能包含字母、数字和下划线)
            'password'=>['required','regex:/^[a-zA-Z]\w{5,17}$/'],
            'phone'=>['required','regex:/^1[3456789]\d{9}$/']
        ]);
        return !$validator->fails();
    }
}
