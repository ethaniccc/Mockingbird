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
            $user->hitData->inCooldown = Server::getInstance()->getTick() - $this->lastTick < 10;
            if(!$user->hitData->inCooldown){
                $user->timeSinceAttack = 0;
                $this->lastTick = Server::getInstance()->getTick();
                $user->hitData->lastTick = $this->lastTick;
            }
        }
    }

}