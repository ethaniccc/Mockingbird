<?php

namespace ethaniccc\Mockingbird\utils\location;

use pocketmine\math\Vector3;

class Vector4 extends Vector3{

    public $time, $yaw, $pitch;

    public static function fromVector3(Vector3 $vector3) : Vector4{
        return new Vector4($vector3->x, $vector3->y, $vector3->z);
    }

    public function __construct($x, $y, $z, float $yaw = null, float $pitch = null, float $time = null){
        parent::__construct($x, $y, $z);
        $this->yaw = $yaw;
        $this->pitch = $pitch;
        $this->time = $time === null ? microtime(true) * 1000 : $time;
    }

    public function getYaw() : ?float{
        return $this->yaw;
    }

    public function getPitch() : ?float{
        return $this->pitch;
    }

    public function getTime() : float{
        return $this->time;
    }

}