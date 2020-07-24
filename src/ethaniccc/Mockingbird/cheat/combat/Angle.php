<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\PlayerDamageByPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;

class Angle extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(10);
    }

    public function onHit(PlayerDamageByPlayerEvent $event) : void{
        $damager = $event->getDamager();
        if($event->getAngle() > 120){
            $this->addViolation($damager->getName());
            $this->notifyStaff($damager->getName(), $this->getName(), ["VL" => self::getCurrentViolations($damager->getName()), "Angle" => round($event->getAngle(), 0)]);
        }
    }

}