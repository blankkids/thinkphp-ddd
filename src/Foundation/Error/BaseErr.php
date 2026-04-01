<?php

namespace Blankkids\ThinkphpDdd\Foundation\Error;

class BaseErr
{

    protected static $data=[
    ];

    static function code($type){
        return self::$data[$type]['code'];
    }

    static function msg($type){
        return self::$data[$type]['msg'];
    }

}