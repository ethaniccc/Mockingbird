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

    private $awaitingMove = false;
    private $lastDirectionVector;

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
        if($packet instanceof InventoryTransactionPacket && $user->win10 && !$user->player->isCreative() && !$this->awaitingMove && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK && $user->isDesktop && !$user->player->isCreative() && $user->tickData->targetLocationHistory !== null){
            if($user->tickData->targetLocationHistory->getLocations()->size() >= floor($user->transactionLatency / 50) + 2){
                // wait for the next PlayerAuthInputPacket from the client
                $this->awaitingMove = true;
            }
        } elseif($packet instanceof PlayerAuthInputPacket){
            if($this->awaitingMove){
                $locations = serialize($user->tickData->targetLocationHistory->getLocationsRelativeToTime($user->tickData->currentTick - floor($user->transactionLatency / 50), 3));
                [$from, $to] = [serialize(new Ray($user->moveData->lastLocation->add(0, $user->isSneaking ? 1.52 : 1.62, 0), $this->lastDirectionVector)), serialize(Ray::fromUser($user))];
                $this->getPlugin()->calculationThread->addToTodo(function() use ($locations, $from, $to){
                    [$locations, $from, $to] = [unserialize($locations), unserialize($from), unserialize($to)];
                    $collided = 0;
                    foreach($locations as $AABB){
                        /** @var AABB $AABB */
                        if($AABB->collidesRay($from, 0, 10) !== -69.0){
                            $collided++;
                        }
                        if($AABB->collidesRay($to, 0, 10) !== -69.0){
                            $collided++;
                        }
                    }
                    return $collided;
                }, function($result) use(&$user){
                    if($result !== null && $user !== null){
                        // there was no collision to the AABB
                        if($result === 0 && $user->responded){
                            // this is only going to flag blatant hitbox, but worth it over false positives (for now)
                            if(++$this->preVL >= 7){
                                $this->fail($user, 'collided=false buff=' . $this->preVL);
                            }
                        } else {
                            $this->reward($user, 0.999);
                            $this->preVL = 0;
                        }
                        if($this->isDebug($user)){
                            $collided = $result ? 'true' : 'false';
                            $user->sendMessage("collided=$collided count=$result");
                        }
                    }
                });
                $this->awaitingMove = false;
            }
            $this->lastDirectionVector = $user->moveData->directionVector;
        }
    }

}