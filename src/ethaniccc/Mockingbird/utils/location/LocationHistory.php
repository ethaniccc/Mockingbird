<?php

namespace ethaniccc\Mockingbird\utils\location;

use pocketmine\math\Vector3;

class LocationHistory{

    public $locations = [];

    public function addLocation(Vector3 $pos) : void{
        if(count($this->locations) === 40){
            $this->locations[0] = null;
            array_shift($this->locations);
        }
        $info = new \stdClass();
        $info->pos = $pos;
        $info->time = microtime(true);
        $this->locations[] = $info;
    }

    public function getLocations() : array{
        return $this->locations;
    }

}