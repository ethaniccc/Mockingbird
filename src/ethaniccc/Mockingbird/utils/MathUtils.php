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

    public static function vectorAngle(Vector3 $a, Vector3 $b) : float{
        $dot = $a->dot($b) / ($a->length() * $b->length());
        return acos($dot);
    }

    public static function directionVectorFromValues(float $yaw, float $pitch) : Vector3{
        $vector = new Vector3(0, 0, 0);
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        return $vector->setComponents($x, $y, $z)->normalize();
    }

    public static function getKurtosis(array $data) : float{
        try{
            $sum = array_sum($data);
            $count = count($data);

            if($count < 3){
                return 0;
            }

            $efficiencyFirst = $count * ($count + 1) / (($count - 1) * ($count - 2) * ($count - 3));
            $efficiencySecond = 3 * pow($count - 1, 2) / (($count - 2) * ($count - 3));
            $average = $sum / $count;

            $variance = 0.0;
            $varianceSquared = 0.0;

            foreach($data as $number){
                $variance += pow($average - $number, 2);
                $varianceSquared += pow($average - $number, 4);
            }

            if($variance === 0.0){
                return 0.0;
            }

            return $efficiencyFirst * ($varianceSquared / pow($variance / $sum, 2)) - $efficiencySecond;
        } catch(\ErrorException $e){
            return 0.0;
        }
    }

    public static function getSkewness(array $data) : float{
        try{
            $sum = array_sum($data);
            $count = count($data);

            $numbers = $data;
            sort($numbers);

            $mean = $sum / $count;
            $median = ($count % 2 !== 0) ? $numbers[$count / 2] : ($numbers[($count - 1) / 2] + $numbers[$count / 2]) / 2;
            $variance = self::getVariance($data);

            return $variance > 0 ? 3 * ($mean - $median) / $variance : 0;
        } catch(\ErrorException $e){
            return 0.0;
        }
    }

    public static function getVariance(array $data) : float{
        $variance = 0;
        $mean = array_sum($data) / count($data);

        foreach ($data as $number) {
            $variance += pow($number - $mean, 2);
        }

        return $variance / count($data);
    }

    public static function getOutliers(array $collection) : \stdClass{
        $q1 = self::getMedian(array_splice($collection, 0, (int) ceil(count($collection) / 2)));
        $q3 = self::getMedian(array_splice($collection, (int) ceil(count($collection) / 2), count($collection)));

        $iqr = abs($q1 - $q3);
        $lowThreshold = $q1 - 1.5 * $iqr;
        $highThreshold = $q3 + 1.5 * $iqr;

        $x = [];
        $y = [];

        foreach($collection as $value) {
            if ($value < $lowThreshold) {
                $x[] = $value;
            } elseif ($value > $highThreshold) {
                $y[] = $value;
            }
        }

        $pair = new \stdClass();
        $pair->x = $x;
        $pair->y = $y;
        return $pair;
    }

    public static function getMedian(array $data) : float{
        if (count($data) % 2 === 0) {
            return ($data[count($data) / 2] + $data[count($data) / 2 - 1]) / 2;
        } else {
            return $data[count($data) / 2];
        }
    }

    public static function getGCD(float $a, float $b) : float{
        if($a < $b){
            return self::getGCD($b, $a);
        }
        if(abs($b) < 0.001){
            return $a;
        } else {
            return self::getGCD($b, $a - floor($a / $b) * $b);
        }
    }

    public static function getArrayGCD(array $nums) : float{
        if(count($nums) < 2){
            return 0.0;
        }
        $result = $nums[0];
        for($i = 1; $i < count($nums); $i++){
            $result = self::getGCD($nums[$i], $result);
        }
        return $result;
    }

    public static function getMostCommonNumber(array $nums) : int{
        $values = array_count_values($nums);
        asort($values);
        return array_slice(array_keys($values), 0, 5, true)[0];
    }

}