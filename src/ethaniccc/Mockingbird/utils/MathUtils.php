<?php

/*
$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\utils;

use ErrorException;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MathUtils{

    public static function isRoughlyEqual(float $d1, float $d2) : bool{
        return abs($d1 - $d2) < 0.015;
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

    public static function getGCD(array $numbers) : float{
        if(count($numbers) === 1 || count($numbers) === 0){
            return 1;
        }
        $result = $numbers[0];
        for($i = 1; $i < count($numbers); $i++){
            $result = self::gcd($result, $numbers[$i]);
        }
        return $result;
    }

    private static function gcd(float $a, float $b) : float{
        if($a < $b){
            return self::gcd($b, $a);
        }
        if(abs($b) < 0.001){
            return $b;
        } else {
            return self::gcd($b, $a - floor($a / $b) * $b);
        }
    }

    public static function getTimeMS() : float{
        return microtime(true) * 1000;
    }

    public static function getPerfectAim(Vector3 $player, Vector3 $target) : array{
        $horizontal = sqrt(($target->getX() - $player->getX()) ** 2 + ($target->getZ() - $player->getZ()) ** 2);
        $vertical = $target->getY() - $player->getY();
        $perfectPitch = -atan2($vertical, $horizontal) / M_PI * 180;

        $xDist = $target->getX() - $player->getX();
        $zDist = $target->getZ() - $player->getZ();
        $perfectYaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($perfectYaw < 0){
            $perfectYaw += 360.0;
        }
        return ["Yaw" => $perfectYaw, "Pitch" => $perfectPitch];
    }

    public static function microtimeToTicks(float $time) : float{
        $scaledTime = round($time * 1000, 0);
        $timePerTick = 50;
        $estimatedTime = $scaledTime / $timePerTick;
        return round($estimatedTime, 0);
    }

    public static function getDirectionVector(float $yaw, float $pitch) : Vector3{
        $vector = new Vector3(0, 0, 0);
        $rotX = deg2rad($yaw);
        $rotY = deg2rad($pitch);
        $vector->y = -sin($rotY);
        $xz = cos($rotY);
        $vector->x = -$xz * sin($rotX);
        $vector->z = $xz * cos($rotX);
        return $vector;
    }

    public static function angle(Vector3 $vec1, Vector3 $vec2) : float{
        try{
            $dot = min(max($vec1->dot($vec2) / ($vec1->length() * $vec2->length()), -1), 1);
            return acos($dot);
        } catch(ErrorException $e){
            return 0;
        }
    }

}