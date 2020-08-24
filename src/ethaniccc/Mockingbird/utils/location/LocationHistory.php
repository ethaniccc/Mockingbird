<?php

namespace ethaniccc\Mockingbird\utils\location;

use pocketmine\Player;

class LocationHistory{

    private $player;
    private $maxSize;
    /** @var Vector4[] */
    private $locations = [];

    public function __construct(Player $player, int $maxSize = 40){
        $this->player = $player;
        $this->maxSize = $maxSize;
    }

    public function addLocation(Vector4 $location) : void{
        if(count($this->locations) >= $this->maxSize){
            array_shift($this->locations);
        }
        $this->locations[] = $location;
    }

    public function getLastSentLocation() : ?Vector4{
        return count($this->locations) === 0 ? null : end($this->locations);
    }

    public function getLocationRelativeToTime(float $time) : ?Vector4{
        $probables = [];
        foreach($this->locations as $location){
            if($location->getTime() <= $time){
                $probables[round($location->getTime(), 0)] = $location;
            }
        }
        ksort($probables);
        return end($probables) !== false ? end($probables) : null;
    }

}