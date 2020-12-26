<?php

namespace ethaniccc\Mockingbird\utils\boundingbox;

// thanks shura62!
use ethaniccc\Mockingbird\user\User;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class Ray{

    /** @var Vector3 */
    private $origin, $direction;

    public static function from(Entity $player) : Ray{
        return new Ray($player->add(0, $player->getEyeHeight(), 0), $player->getDirectionVector());
    }

    public static function fromUser(User $user) : Ray{
        return new Ray($user->moveData->location->add(0, $user->player->getEyeHeight(), 0), $user->moveData->directionVector);
    }

    public function __construct(Vector3 $origin, Vector3 $direction){
        $this->origin = $origin;
        $this->direction = $direction;
    }

    public function origin(int $i) : float{
        return [$this->origin->getX(), $this->origin->getY(), $this->origin->getZ()][$i] ?? 0.001;
    }

    public function direction(int $i) : float{
        return [$this->direction->getX(), $this->direction->getY(), $this->direction->getZ()][$i] ?? 0.001;
    }

    public function getOrigin() : Vector3{
        return $this->origin;
    }

    public function getDirection() : Vector3{
        return $this->direction;
    }

}