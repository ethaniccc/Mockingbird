<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\SizedList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ReachB extends Detection{

    private $awaitingMove = false;
    private $lastDirectionVector;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && !$this->awaitingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK && $user->hitData->targetEntity === $user->hitData->lastTargetEntity){
            if($user->tickData->targetLocationHistory->getLocations()->size() >= floor($user->transactionLatency / 50) + 2){
                // wait for the next PlayerAuthInputPacket from the client
                $this->awaitingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket){
            if($this->awaitingMove){
                // the client is off by at least one tick
                $locations = serialize($user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - (floor($user->transactionLatency / 50) + 1), 2));
                [$from, $to] = [serialize(new Ray($user->moveData->lastLocation->add(0, $user->isSneaking ? 1.54 : 1.62, 0), $this->lastDirectionVector)), serialize(Ray::fromUser($user))];
                $latency = $user->transactionLatency;
                $this->getPlugin()->calculationThread->addToTodo(function() use($locations, $from, $to, $latency){
                    [$locations, $from, $to] = [unserialize($locations), unserialize($from), unserialize($to)];
                    $lastLocation = null;
                    $distances = new SizedList(80);
                    foreach($locations as $location){
                        if($lastLocation === null){
                            $moveDelta = 0;
                        } else {
                            // see: https://media.discordapp.net/attachments/727159224320131133/795030256523935784/unknown.png?width=1049&height=316
                            $moveDelta = $location->distance($lastLocation) / ($latency > 50 ? 2 : 3);
                        }
                        $AABB = AABB::fromPosition($location->subtract($moveDelta))->expand(0.1, 0.1, 0.1);
                        $distance = $AABB->collidesRay($from, 10);
                        if($distance !== -69.0){
                            $distances->add($distance);
                        }
                        $distance = $AABB->collidesRay($to, 10);
                        if($distance !== -69.0){
                            $distances->add($distance);
                        }
                        $lastLocation = $location;
                    }
                    return $distances->minOrElse(-1.0);
                }, function($distance) use ($user){
                    if($distance !== -1.0 && $distance !== null){
                        // make sure the user's latency is updated and that the distance is greater than the allowed
                        if($distance > $this->getSetting('max_reach')){
                            if($user->responded){
                                if(++$this->preVL >= 3){
                                    $roundedDist = round($distance, 3);
                                    $this->fail($user, "(B) dist=$distance buff={$this->preVL}", "dist=$roundedDist");
                                }
                                $this->preVL = min($this->preVL, 3.1);
                            }
                        } else {
                            $this->preVL = max($this->preVL - 0.05, 0);
                        }
                    }
                    if($this->isDebug($user)){
                        $user->sendMessage("dist=$distance buff={$this->preVL}");
                    }
                });
                $this->awaitingMove = false;
            }
            $this->lastDirectionVector = $user->moveData->directionVector;
        }
    }

}
