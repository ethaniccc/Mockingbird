<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Consumable;
use pocketmine\event\player\PlayerMoveEvent;

class NoSlowdown extends Cheat{

    private $startedEatingTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        $from = $event->getFrom();
        $to = $event->getTo();

        $distX = ($to->x - $from->x);
        $distZ = ($to->z - $from->z);

        $distanceSquared = abs(($distX * $distX) + ($distZ * $distZ));
        $distance = sqrt($distanceSquared);

        if($player->isUsingItem()){
            $item = $player->getInventory()->getItemInHand();
            if($item instanceof Consumable){
                if($player->getFood() == $player->getMaxFood()) return;
                if(!isset($this->startedEatingTick[$name])){
                    $this->startedEatingTick[$name] = $this->getServer()->getTick();
                    return;
                } else {
                    if($this->getServer()->getTick() - $this->startedEatingTick[$name] < 20){
                        return;
                    }
                }
                if($distance > 0.1){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
            }
        } else {
            if(isset($this->startedEatingTick[$name])) unset($this->startedEatingTick[$name]);
        }
    }

}