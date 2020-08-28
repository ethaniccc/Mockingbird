<?php

namespace ethaniccc\Mockingbird\utils\raytrace;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

// useless class lol pls ignore
class RayTrace{

    private $origin, $direction;

    public static function from(Entity $entity) : RayTrace{
        return new RayTrace($entity->add(0, $entity->getEyeHeight(), 0), $entity->getDirectionVector());
    }

    public function __construct(Vector3 $origin, Vector3 $direction){
        $this->origin = $origin;
        $this->direction = $direction;
    }

    public function getPosition(float $blocksAway) : Vector3{
        return $this->origin->add($this->direction->multiply($blocksAway));
    }

    public function traverse(float $blocksAway, float $accuracy) : array{
        $positions = [];
        for($d = 0; $d <= $blocksAway; $d += $accuracy){
            $positions[] = $this->getPosition($d);
        }
        return $positions;
    }

}