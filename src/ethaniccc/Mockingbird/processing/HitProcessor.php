<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\level\particle\FlameParticle;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;
use pocketmine\Server;

class HitProcessor extends Processor{

    private $lastTick;

    public function __construct(User $user){
        parent::__construct($user);
        $this->lastTick = Server::getInstance()->getTick();
    }

    public function process(DataPacket $packet): void{
        $user = $this->user;
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            $user->hitData->attackPos = $packet->trData->playerPos;
            $user->hitData->targetEntity = $user->player->getLevel()->getEntity($packet->trData->entityRuntimeId);
            $entity = $user->hitData->targetEntity;
            if($entity instanceof Player){
                // only do this if the entity is a player
                $damagedUser = UserManager::getInstance()->get($entity);
                $estimatedTime = (microtime(true) * 1000) - $user->transactionLatency;
                $distances = [];
                foreach($damagedUser->locationHistory->getLocationsRelativeToTime($estimatedTime, 100) as $location){
                    $AABB = AABB::fromPosition($location)->expand(0.1, 0, 0.1);
                    $AABB->maxY = $AABB->minY + 1.9;
                    $distance = $AABB->collidesRay(new Ray($user->hitData->attackPos, $user->moveData->directionVector), 7);
                    if($distance !== -69.0){
                        $distances[] = $distance;
                    }
                }
                if(count($distances) === 0){
                    $user->hitData->rayDistance = -69.0;
                    $user->hitData->rayCollides = false;
                } else {
                    $user->hitData->rayDistance = min($distances);
                    $user->hitData->rayCollides = true;
                }
            }
            $user->hitData->inCooldown = Server::getInstance()->getTick() - $this->lastTick < 10;
            if(!$user->hitData->inCooldown){
                $this->lastTick = Server::getInstance()->getTick();
            }
            $user->timeSinceAttack = 0;
        }
    }

}