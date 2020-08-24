<?php

namespace ethaniccc\Mockingbird\utils\location;

use pocketmine\math\Vector3;

class Vector4 extends Vector3{

    public $time;

    public function __construct($x, $y, $z, float $time = null){
        parent::__construct($x, $y, $z);
        $this->time = $time === null ? microtime(true) * 1000 : $time;
    }

    public function getTime() : float{
        return $this->time;
    }

}