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
        return hypot($a->x - $b->x, $a->z - $b->z);
    }

    public static function directionVectorFromValues(float $yaw, float $pitch) : Vector3{
        $vector = new Vector3(0, 0, 0);
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        return $vector->setComponents($x, $y, $z)->normalize();
    }

}