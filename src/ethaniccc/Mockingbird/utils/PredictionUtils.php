<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;

class PredictionUtils{

    public static function moveFlying(float $forward, float $strafe, float $friction, float $yaw) : Vector3{
        $var1 = ($forward ** 2) + ($strafe ** 2);
        if($var1 >= 1E-4){
            $var1 = max(sqrt($var1), 1);
            $var1 = $friction / $var1;
            $strafe *= $var1;
            $forward *= $var1;
            $var2 = MathUtils::sin($yaw * M_PI / 180);
            $var3 = MathUtils::cos($yaw * M_PI / 180);
            return new Vector3($strafe * $var3 - $forward * $var2, 0, $forward * $var3 + $strafe * $var2);
        }
        return new Vector3(0, 0, 0);
    }

}