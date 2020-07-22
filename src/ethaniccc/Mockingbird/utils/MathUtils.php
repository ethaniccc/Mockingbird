<?php

namespace ethaniccc\Mockingbird\utils;

class MathUtils{

    public static function getGCD(array $numbers) : float{
        return array_reduce($numbers, function($a, $b){
            return self::gcd($a, $b);
        });
    }

    private static function gcd($a, $b){
        return $b ? self::gcd($b, $a % $b) : $a;
    }

}