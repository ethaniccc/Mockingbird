<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\utils\TextFormat;

/**
 * Class ReachA
 * @package ethaniccc\Mockingbird\detections\combat\reach
 * ReachA uses locations the client has received of the entity and
 * creates bounding boxes from those locations. With those bounding boxes, we get the distance from the user's
 * current eye pos and last eye pos to the bounding boc [@see AABB::distanceFromVector()] and store that in a list, then gets the minimum distance in the list.
 * If the distance exceeds a threshold and the buffer exceeds a level, flag.
 */
class ReachA extends Detection{

    private $awaitingMove = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
    }

    public function handleReceive(DataPacket $packet, User $user) : void{
        if($packet instanceof InventoryTransactionPacket && !$user->player->isCreative() && !$this->awaitingMove && $packet->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK && $user->hitData->targetEntity === $user->hitData->lastTargetEntity){
            if(count($user->tickData->targetLocations) >= 2){
                $this->awaitingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket && $this->awaitingMove){
            $list = new EvictingList(PHP_INT_MAX);
            foreach($user->tickData->targetLocations as $location){
                $AABB = AABB::fromPosition($location);
                $list->add($AABB->distanceFromVector($user->hitData->attackPos));
                $list->add($AABB->distanceFromVector($packet->getPosition()));
                unset($AABB);
            }
            $distance = $list->minOrElse(-1.0);
            if($distance !== -1.0){
                if($distance >= 3.075){
                    if(++$this->preVL >= 4){
                        $roundedDist = round($distance, 2);
                        $this->fail($user, 'dist=' . $distance . ' buff=' . $this->preVL, 'dist=' . $roundedDist);
                        $this->preVL = min($this->preVL, 5);
                    }
                } else {
                    $this->preVL = max($this->preVL - 0.025, 0);
                }
                if($this->isDebug($user)){
                    $user->sendMessage($distance > 3.05 ? TextFormat::RED . 'dist=' . $distance . ' buff=' . $this->preVL : 'dist=' . $distance . ' buff=' . $this->preVL);
                }
            }
            $this->awaitingMove = false;
        }
    }

}