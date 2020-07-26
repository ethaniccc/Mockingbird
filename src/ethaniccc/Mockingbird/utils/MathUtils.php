<?php

namespace ethaniccc\Mockingbird\utils;

class MathUtils{

    public static function isRoughlyEqual(float $d1, float $d2) : bool{
        return abs($d1 - $d2) < 0.001;
    }

    public static function getAverage(array $numbers) : float{
        return array_sum($numbers) / count($numbers);
    }

    public static function getDeviation(array $numbers) : float{
        $deviation = 0;
        $mean = array_sum($numbers) / count($numbers);
        foreach ($numbers as $num) {
            $deviation += pow($num - $mean, 2);
        }

        return sqrt($deviation / count($numbers));
    }

    public static function microtimeToTicks(float $time) : float{
        $scaledTime = round($time * 1000, 0);
        $timePerTick = 50;
        $estimatedTime = $scaledTime / $timePerTick;
        return round($estimatedTime, 0);
    }

}