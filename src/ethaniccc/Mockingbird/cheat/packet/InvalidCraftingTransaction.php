<?php

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\inventory\transaction\CraftingTransaction;

class InvalidCraftingTransaction extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, false);
        $this->setMaxViolations(1);
        $this->getServer()->getLogger()->debug("Unfinished check.");
    }

    /**
     * @param DataPacketReceiveEvent $event
     */
    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof InventoryTransactionPacket){
            if(empty($packet->actions) && $packet->transactionType !== InventoryTransactionPacket::TYPE_USE_ITEM){
                var_dump($packet);
                $this->addViolation($event->getPlayer()->getName());
            }
        }
    }

}