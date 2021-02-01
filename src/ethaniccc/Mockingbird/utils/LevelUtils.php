<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\level\particle\FlameParticle;

class LevelUtils{

    public static function userIsOnGround(User $user) : bool{
        $AABB = clone $user->moveData->AABB;
        // frick you lily pad
        $AABB->expand(0.1, 0, 0.1);
        // I wouldn't do this, but because PMMP's onGround is accurate when on cactus, fuck it all
        $AABB->minY -= 0.2;
        return count($user->player->getLevel()->getCollisionBlocks($AABB, true)) !== 0;
    }

}