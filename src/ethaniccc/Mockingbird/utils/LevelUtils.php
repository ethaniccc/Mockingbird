<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\level\particle\FlameParticle;

class LevelUtils{

    public static function userIsOnGround(User $user) : bool{
        // thank you @very nice name#6789, bounding boxes are yummy!
        $AABB = AABB::from($user);
        $AABB->minY -= 0.01;
        $AABB->expand(0.1, 0, 0.1);
        $minX = (int) floor($AABB->minX - 1);
        $minY = (int) floor($AABB->minY - 1);
        $minZ = (int) floor($AABB->minZ - 1);
        $maxX = (int) floor($AABB->maxX + 1);
        $maxY = (int) floor($AABB->maxY + 1);
        $maxZ = (int) floor($AABB->maxZ + 1);
        for($z = $minZ; $z <= $maxZ; $z++){
            for($x = $minX; $x <= $maxX; $x++){
                for($y = $minY; $y <= $maxY; $y++){
                    $block = $user->player->getLevel()->getBlockAt($x, $y, $z);
                    if($block->getId() !== 0){
                        $collisionBoxes = $block->getCollisionBoxes();
                        if(!empty($collisionBoxes)){
                            foreach($collisionBoxes as $bb2){
                                if($AABB->intersectsWith($bb2)){
                                    return true;
                                }
                            }
                        } else {
                            $blockBB = AABB::fromBlock($block);
                            if($AABB->intersectsWith($blockBB)){
                                return true;
                            }
                            unset($blockBB);
                        }
                    }
                }
            }
        }
        $expand = 0.3;
        for($x = -$expand; $x <= $expand; $x += $expand){
            for($z = -$expand; $z <= $expand; $z += $expand){
                if($user->player->getLevel()->getBlock($user->moveData->location->add($x, -0.5001, $z))->getId() !== 0){
                    return true;
                }
            }
        }
        return false;
    }

}