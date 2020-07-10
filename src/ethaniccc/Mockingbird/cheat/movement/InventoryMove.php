<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;

class InventoryMove extends Cheat implements StrictRequirements, Blatant{

    private $lastMoveTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(5);
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $name = $player->getName();

        if(!isset($this->lastMoveTick[$name])){
            return;
        }

        $timeDiff = $this->getServer()->getTick() - $this->lastMoveTick[$name];
        if($timeDiff == 0){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
        }
    }

    public function onMove(PlayerMoveEvent $event) : void{
        if($event->getTo()->getX() - $event->getFrom()->getX() == 0 && $event->getTo()->getZ() - $event->getFrom()->getZ() == 0){
            return;
        }

        $distX = $event->getTo()->getX() - $event->getFrom()->getX();
        $distZ = $event->getTo()->getZ() - $event->getFrom()->getZ();
        $distanceSquared = ($distX * $distX) + ($distZ * $distZ);
        $distance = sqrt($distanceSquared);
        if($distance < 0.1){
            return;
        }
        $this->lastMoveTick[$event->getPlayer()->getName()] = $this->getServer()->getTick();
    }

}