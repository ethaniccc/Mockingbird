<?php

namespace ethaniccc\Mockingbird\utils;

class MathUtils{

    public static function isRoughlyEqual(float $d1, float $d2) : bool{
        return abs($d1 - $d2) < 0.001;
    }

}