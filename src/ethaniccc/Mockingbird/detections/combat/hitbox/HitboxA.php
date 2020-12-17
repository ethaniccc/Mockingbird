<?php

namespace ethaniccc\Mockingbird\detections\combat\hitbox;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class HitboxA
 * @package ethaniccc\Mockingbird\detections\combat\hitbox
 * HitboxA gets a list of locations using "location history", makes a bounding box from the locations, and
 * see if the user's direction can intersect with those bounding boxes. This check is heavy on performance however,
 * and therefore is disabled until a better solution for hitbox is found.
 */
class HitboxA extends Detection{

    private $appendingMove = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        $this->lowMax = 2;
        $this->mediumMax = 3;
        // TODO: this is slow, find a better way to detect hitbox.
        $this->enabled = false;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && !$this->appendingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK && $user->isDesktop && !$user->player->isCreative()){
            if($user->tickData->targetLocationHistory->getLocations()->full()){
                $this->appendingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket && $this->appendingMove){
            $locations = $user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - floor($user->transactionLatency / 50), 2);
            $collided = 0;
            $ray = new Ray($packet->getPosition(), $user->moveData->directionVector);
            foreach($locations as $location){
                if(AABB::fromPosition($location)->expand(0.125, 0, 0.125)->collidesRay($ray, 7) !== -69.0){
                    ++$collided;
                }
            }
            if($collided === 0){
                if(++$this->preVL >= 10){
                    $this->preVL = min($this->preVL, 6);
                    $this->fail($user, "collided=0 buff={$this->preVL}");
                }
            } else {
                $this->preVL = max($this->preVL - 1, 0);
            }
            if($this->isDebug($user)){
                $user->sendMessage("collided=$collided buff={$this->preVL}");
            }
            $this->appendingMove = false;
        }
    }

}