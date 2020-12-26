<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\SizedList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class ReachA
 * @package ethaniccc\Mockingbird\detections\combat\reach
 * ReachA gets estimated locations that the target entity may be on (on the client side), and
 * makes bounding boxes from those locations. With those bounding boxes, we get the distance from the user's
 * current eye pos and last eye pos to the bounding boc [@see AABB::distanceFromVector()] and store that in a list, then gets the minimum distance in the list.
 * If the minimum distance is greater than the threshold, and the preVL reaches it's threshold, then the user is flagged.
 */
class ReachA extends Detection{

    private $awaitingMove = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && !$this->awaitingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if($user->tickData->targetLocationHistory->getLocations()->size() >= floor($user->transactionLatency / 50) + 2){
                // wait for the next PlayerAuthInputPacket from the client
                $this->awaitingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket && $this->awaitingMove){
            $locations = $user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - floor($user->transactionLatency / 50), 2);
            // 40 is the max location history size in the tick data
            $distances = new SizedList(80);
            $lastLocation = null;
            foreach($locations as $location){
                if($lastLocation === null){
                    $moveDelta = 0;
                } else {
                    $moveDelta = $location->subtract($lastLocation)->length();
                }
                $AABB = AABB::fromPosition($location)->expand(0.1, 0.1, 0.1);
                // add the distance from the "to" position to the AABB
                $distances->add($AABB->distanceFromVector($packet->getPosition()) - $moveDelta);
                // add the distance from the "from" position to the AABB
                $distances->add($AABB->distanceFromVector($user->moveData->lastLocation->add(0, 1.62, 0)) - $moveDelta);
                $lastLocation = $location;
            }
            $distance = $distances->minOrElse(-1);
            if($distance !== -1){
                // make sure the user's latency is updated and that the distance is greater than the allowed
                if($distance > $this->getSetting("max_reach")){
                    if($user->responded){
                        $this->preVL += 1.5;
                        if($this->preVL >= 3.1){
                            $this->preVL = min($this->preVL, 9);
                            $rounded = round($distance, 3);
                            $this->fail($user, "dist=$distance", "dist=$rounded buff={$this->preVL}");
                        }
                    }
                } else {
                    $this->reward($user, 0.9995);
                    $this->preVL = max($this->preVL - 0.75, 0);
                }
            }
            if($this->isDebug($user)){
                $user->sendMessage("dist=$distance buff={$this->preVL}");
            }
            $this->awaitingMove = false;
        }
    }

}