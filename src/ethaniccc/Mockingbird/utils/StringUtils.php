<?php

namespace ethaniccc\Mockingbird\utils;

class StringUtils{

    public static function after($characters, $inthat){
        if (!is_bool(strpos($inthat, $characters)))
            return substr($inthat, strpos($inthat,$characters)+strlen($characters));
    }

    public static function before($characters, $inthat){
        return substr($inthat, 0, strpos($inthat, $characters));
    }

    public static function before_last($characters, $inthat){
        return substr($inthat, 0, strripos($inthat, $characters));
    }

}