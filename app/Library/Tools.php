<?php

namespace App\Library;
use Ramsey\Uuid\Uuid;

class Tools{

    //获得32位的uuid
    public static function getUuid(){
        return Uuid::uuid1()->getHex();
    }
    //接口的统一返回值
    public static function serverRes($code, $data, $message)
    {
        return response()->json(['code' => $code, 'data' => $data, 'message' => $message]);
    }
}