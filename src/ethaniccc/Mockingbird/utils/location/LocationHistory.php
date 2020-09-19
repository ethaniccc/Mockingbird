<?php

namespace ethaniccc\Mockingbird\utils\location;

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\user\User;
use pocketmine\math\Vector3;
use pocketmine\Player;

class LocationHistory{

    private $player;
    private $user;
    private $maxSize;
    /** @var Vector4[] */
    private $locations = [];
    private $lastGroundLocation;

    public function __construct(User $user, int $maxSize = 40){
        $this->user = $user;
        $this->player = $user->getPlayer();
        $this->maxSize = $maxSize;
    }

    public function addLocation(Vector4 $location) : void{
        if(count($this->locations) >= $this->maxSize){
            array_shift($this->locations);
        }
        if($this->user->getServerOnGround()){
            $this->lastGroundLocation = $location->round(4)->subtract(0, 1.62, 0);
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

    public function getLastOnGroundLocation() : ?Vector3{
        return $this->lastGroundLocation;
    }

}