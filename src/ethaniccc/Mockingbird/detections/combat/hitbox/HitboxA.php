<?php

namespace ethaniccc\Mockingbird\detections\combat\hitbox;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\EvictingList;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

/**
 * Class HitboxA
 * @package ethaniccc\Mockingbird\detections\combat\hitbox
 * HitboxA gets a list of locations using "location history", makes a bounding box from the locations, and
 * see if the user's direction can intersect with those bounding boxes. This check is heavy on performance however,
 * and therefore is disabled until a better solution for hitbox is found.
 */
class HitboxA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 10;
        $this->lowMax = 2;
        $this->mediumMax = 5;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($user->timeSinceJoin < 100 || !$user->loggedIn){
            return;
        }
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && $packet->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK && $user->isDesktop && !$user->player->isCreative()){
            if(count($user->tickData->targetLocations) >= 2){
                $angleList = [];
                foreach($user->tickData->targetLocations as $location){
                    $deltaX = $location->x - $user->hitData->attackPos->x;
                    $deltaZ = $location->z - $user->hitData->attackPos->z;
                    if(MathUtils::hypot($deltaX, $deltaZ) > 2){
                        $directionX = -(MathUtils::sin($user->moveData->yaw * M_PI / 180)) * 0.5;
                        $directionZ = MathUtils::cos($user->moveData->yaw * M_PI / 180) * 0.5;
                        $angleList[] = rad2deg(MathUtils::vectorAngle(new Vector3($deltaX, 0, $deltaZ), new Vector3($directionX, 0, $directionZ)));
                    }
                }
                if(count($angleList) > 0){
                    $minAngle = min($angleList);
                    if($minAngle >= 25){
                        if(++$this->preVL >= 5){
                            $this->fail($user, 'angle=' . $minAngle . ' buff=' . $this->preVL);
                        }
                    } else {
                        $this->preVL = max($this->preVL - 3, 0);
                        $this->reward($user, 0.015);
                    }
                    if($this->isDebug($user)){
                        $user->sendMessage('angle=' . $minAngle . ' buff=' . $this->preVL);
                    }
                }
            }
        }
    }

}