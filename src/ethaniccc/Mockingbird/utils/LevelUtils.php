<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\level\particle\FlameParticle;

class LevelUtils{

    public static function userIsOnGround(User $user) : bool{
        $AABB = clone $user->moveData->AABB;
        $AABB->minY -= 0.05;
        return count($user->player->getLevel()->getCollisionBlocks($AABB, true)) !== 0;
    }

}