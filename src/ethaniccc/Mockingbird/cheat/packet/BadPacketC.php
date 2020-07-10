<?php

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\Blatant;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;

class BadPacketC extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(1);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();

        if($packet instanceof InventoryTransactionPacket){
            foreach($packet->actions as $action){
                if($action->sourceType === NetworkInventoryAction::SOURCE_CREATIVE && !$player->isCreative()){
                    $this->addViolation($name);
                }
            }
        }
    }

}