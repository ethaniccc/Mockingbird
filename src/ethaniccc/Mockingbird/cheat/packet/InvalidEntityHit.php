<?php

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\Blatant;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class InvalidEntityHit extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(1);
    }

    public function receivePacket(DataPacketReceiveEvent $event){
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();

        if($packet instanceof InventoryTransactionPacket){
            if($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
                $target = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
                if($target instanceof ItemEntity or $target instanceof Arrow){
                    $this->addViolation($name);
                }
            }
        }
    }

}