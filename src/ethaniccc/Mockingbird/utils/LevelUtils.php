<?php

namespace ethaniccc\Mockingbird\utils;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\block\BlockIds;

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
                            if(AABB::fromBlock($block)->intersectsWith($AABB)){
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

}