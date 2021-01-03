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
 * This check now also utilizes trust, and if the trust is too low (the player is too un-trustworthy) along with the preVL (buffer) being too high, flag.
 */
class ReachA extends Detection{

    private $awaitingMove = false;
    // half of the max trust - start neutral
    private $trust = 0.75;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof InventoryTransactionPacket && !$user->player->isCreative() && !$this->awaitingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if($user->tickData->targetLocationHistory->getLocations()->size() >= floor($user->transactionLatency / 50) + 2){
                // wait for the next PlayerAuthInputPacket from the client
                $this->awaitingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket && $this->awaitingMove){
            // the client is off by at least one tick
            $locations = serialize($user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - (floor($user->transactionLatency / 50) + 1), 2));
            [$from, $to] = [serialize($user->moveData->lastLocation->add(0, 1.62, 0)), serialize($packet->getPosition())];
            $this->getPlugin()->calculationThread->addToTodo(function() use($locations, $from, $to){
                [$locations, $from, $to] = [unserialize($locations), unserialize($from), unserialize($to)];
                $lastLocation = null;
                $distances = new SizedList(80);
                foreach($locations as $location){
                    if($lastLocation === null){
                        $moveDelta = 0;
                    } else {
                        // see: https://media.discordapp.net/attachments/727159224320131133/795030256523935784/unknown.png?width=1049&height=316
                        $moveDelta = $location->distance($lastLocation) / 3;
                    }
                    $AABB = AABB::fromPosition($location)->expand(0.1, 0.1, 0.1);
                    $distances->add($AABB->distanceFromVector($from) - $moveDelta);
                    $distances->add($AABB->distanceFromVector($to) - $moveDelta);
                    $lastLocation = $location;
                }
                return $distances->minOrElse(-1.0);
            }, function($distance) use ($user){
                if($distance !== -1.0 && $distance !== null){
                    // make sure the user's latency is updated and that the distance is greater than the allowed
                    if($distance > $this->getSetting("max_reach")){
                        if($user->responded){
                            $this->trust = max($this->trust - 0.15, 0);
                            if(++$this->preVL >= 4 && $this->trust <= 0.5){
                                $roundedDist = round($distance, 3);
                                // lower the buffer for *possible*  falses
                                $this->preVL = 3;
                                $this->fail($user, "(A) dist=$distance buff={$this->preVL} trust={$this->trust}", "dist=$roundedDist");
                            }
                            $this->preVL = min($this->preVL, 4.5);
                        }
                    } else {
                        $this->reward($user, 0.9995);
                        $this->preVL = max($this->preVL - 0.05, 0);
                        $this->trust = min($this->trust + 0.01, 1.5);
                    }
                }
                if($this->isDebug($user)){
                    $user->sendMessage("dist=$distance buff={$this->preVL} trust={$this->trust}");
                }
            });
            $this->awaitingMove = false;
        }
    }

}