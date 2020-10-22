<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;

class MathUtils{

    public static function getDeviation(array $nums) : float{
        if(count($nums) < 1){
            return 0.0;
        }
        $variance = 0;
        $average = array_sum($nums) / count($nums);
        foreach($nums as $num){
            $variance += pow($num - $average, 2);
        }
        return sqrt($variance / count($nums));
    }

    public static function getAverage(array $nums) : float{
        if(count($nums) === 0){
            return 0.0;
        }
        return array_sum($nums) / count($nums);
    }

    public static function vectorXZDistance(Vector3 $a, Vector3 $b) : float{
        $a = clone $a;
        $b = clone $b;
        $a->y = 0;
        $b->y = 0;
        return $a->distance($b);
    }

}