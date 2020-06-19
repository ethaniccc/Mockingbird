<?php

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use ethaniccc\Mockingbird\Mockingbird;

class BadPitch extends Cheat{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $name = $damager->getName();

        if(abs($damager->getPitch()) > 90){
            $this->addViolation($name);
            $data = [
                "Pitch" => round(abs($damager->getPitch()), 3),
                "VL" => $this->getCurrentViolations($name),
                "Ping" => $damager->getPing()
            ];
            $this->notifyStaff($name, $this->getName(), $data);
        } 
    }

}