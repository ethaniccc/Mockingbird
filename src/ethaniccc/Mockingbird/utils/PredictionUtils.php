<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;

class PredictionUtils{

    public static function moveFlying(float $forward, float $strafe, float $friction, float $yaw, Vector3 &$moveDelta) : void{
        $var1 = ($forward ** 2) + ($strafe ** 2);
        if($var1 > 1E-4){
            $var1 = sqrt($var1);
            if($var1 < 1){
                $var1 = 1;
            }
            $var1 = $friction / $var1;
            $strafe *= $var1;
            $forward *= $var1;
            $var2 = sin($yaw * 3.141592653589793 / 180);
            $var3 = cos($yaw * 3.141592653589793 / 180);
            $moveDelta->x += $strafe * $var3 - $forward * $var2;
            $moveDelta->z += $forward * $var3 + $strafe * $var2;
        }
    }

}