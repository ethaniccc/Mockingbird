<?php

namespace ethaniccc\Mockingbird\utils\location;

use ethaniccc\Mockingbird\utils\SizedList;
use pocketmine\math\Vector3;

class LocationHistory{

    /** @var SizedList */
    public $locations;

    public function __construct(){
        $this->locations = new SizedList(40);
    }

    public function addLocation(Vector3 $pos, int $tick) : void{
        $info = new \stdClass();
        $info->pos = $pos;
        $info->tick = $tick;
        $this->locations->add($info);
    }

    public function getLocations() : SizedList{
        return $this->locations;
    }

    /**
     * @param int $tick
     * @param int $diff
     * @return Vector3[]
     */
    public function getLocationsRelativeToTime(int $tick, int $diff = 1) : array{
        $locations = [];
        foreach($this->getLocations()->get() as $data){
            if($tick - $data->tick <= $diff){
                $locations[] = $data->pos;
            }
        }
        return $locations;
    }

}