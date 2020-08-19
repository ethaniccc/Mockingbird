<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;

class Vector4 extends Vector3{

    public $time;

    public function __construct($x, $y, $z, float $time){
        parent::__construct($x, $y, $z);
        $this->time = $time;
    }

}