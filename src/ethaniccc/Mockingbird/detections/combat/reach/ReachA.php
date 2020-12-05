<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ReachA extends Detection{

    private $appendingMove = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 20;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && !$this->appendingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if($user->tickData->targetLocationHistory->getLocations()->full()){
                $this->appendingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket && $this->appendingMove){
            $locations = $user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - floor($user->transactionLatency / 50), 2);
            $distances = [];
            $ray = new Ray($packet->getPosition(), $user->moveData->directionVector);
            $origin = clone $packet->getPosition();
            $origin->y = 0;
            foreach($locations as $location){
                if($this->getSetting("lightweight")){
                    $loc = new Vector3($location->x, 0, $location->z);
                    // hardcoded value of hypot(0.4, 0.4) - 0.4 being hitbox for X and Z
                    $widthXZ = 0.56568542494924 + 0.15;
                    // 0.15 added to widthXZ for more leniency
                    $distances[] = $origin->distance($loc) - $widthXZ;
                } else {
                    $AABB = AABB::fromPosition($location)->expand(0.1, 0.1, 0.1);
                    $distance = $AABB->collidesRay($ray, 7);
                    if($distance !== -69.0){
                        $distances[] = $distance;
                    }
                }
            }
            // this can still occur even with lightweight mode enabled.
            if(count($distances) === 0){
                return;
            }
            if($this->getSetting("lightweight")){
                $distance = min($distances);
                if($distance > $this->getSetting("max_reach")){
                    $this->preVL += 1.5;
                    if($this->preVL >= 3){
                        $this->preVL = min($this->preVL, 9);
                        $this->fail($user, "dist=$distance light=true buff={$this->preVL}");
                    }
                } else {
                    $this->preVL = max($this->preVL - 0.75, 0);
                }
            } else {
                $distance = min($distances);
                if($distance > $this->getSetting("max_reach")){
                    if(++$this->preVL >= 5){
                        $this->preVL = min($this->preVL, 10);
                        $this->fail($user, "dist=$distance light=false buff={$this->preVL}");
                    }
                } else {
                    $this->reward($user, 0.9995);
                    $this->preVL = max($this->preVL - 0.3, 0);
                }
            }
            if($this->isDebug($user)){
                $light = $this->getSetting("lightweight") ? 'true' : 'false';
                $user->sendMessage("light=$light dist=$distance buff={$this->preVL}");
            }
            $this->appendingMove = false;
        }
    }

}