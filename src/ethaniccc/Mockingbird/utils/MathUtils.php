<?php

namespace ethaniccc\Mockingbird\utils;

class MathUtils{

    public static function getDeviation(array $nums) : float{
        $variance = 0;
        $average = array_sum($nums) / count($nums);
        foreach($nums as $num){
            $variance += pow($num - $average, 2);
        }
        return sqrt($variance / count($nums));
    }

    public static function getAverage(array $nums) : float{
        return array_sum($nums) / count($nums);
    }

}