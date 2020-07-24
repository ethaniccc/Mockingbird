<?php

/*
$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\ 
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |                                  
                                                         \$$$$$$  |                                  
                                                          \______/      
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc                             
*/ 

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\PlayerDamageByPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;

class ReachB extends Cheat implements StrictRequirements{

    private $lastHit = [];
    private $lastLastHit = [];

    private $cooldown = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(PlayerDamageByPlayerEvent $event) : void{
        $damager = $event->getDamager();
        // For some reason, EntityDamageByEntityEvent is still called even when
        // cooldown is in place.
        if(!isset($this->cooldown[$damager->getName()])){
            $this->cooldown[$damager->getName()] = $this->getServer()->getTick();
        } else {
            if($this->getServer()->getTick() - $this->cooldown[$damager->getName()] >= 10){
                $this->cooldown[$damager->getName()] = $this->getServer()->getTick();
            } else {
                return;
            }
        }

        if(!isset($this->lastHit[$damager->getName()])){
            $baseAllowed = $this->getAllowedDistance();
            if($damager->getPing() >= 200) $baseAllowed += $damager->getPing() * 0.003;
            if($damager->isCreative()) $baseAllowed = 7.5;
            $this->lastHit[$damager->getName()] = [
                "distance" => $event->getVectorDistanceXZ(),
                "expected" => $baseAllowed
            ];
        } elseif(!isset($this->lastLastHit[$damager->getName()])){
            $baseAllowed = $this->getAllowedDistance();
            if($damager->getPing() >= 200) $baseAllowed += $damager->getPing() * 0.003;
            if($damager->isCreative()) $baseAllowed = 7.5;
            $this->lastLastHit[$damager->getName()] = [
                "distance" => $event->getVectorDistanceXZ(),
                "expected" => $baseAllowed
            ];
        } else {
            $baseAllowed = $this->getAllowedDistance();
            if($damager->isCreative()) $baseAllowed = 7.5;
            $this->getServer()->broadcastMessage("{$event->getVectorDistanceXZ()}");
            if($event->getVectorDistanceXZ() > $baseAllowed){
                if($this->lastHit[$damager->getName()]["distance"] > $this->lastHit[$damager->getName()]["expected"]){
                    if($this->lastLastHit[$damager->getName()]["distance"] > $this->lastLastHit[$damager->getName()]["expected"]){
                        $this->addViolation($damager->getName());
                        $data = [
                            "VL" => self::getCurrentViolations($damager->getName()) + 1,
                            "Distance" => round($event->getVectorDistanceXZ(), 2),
                            "Ping" => $damager->getPing()
                        ];
                        $this->notifyStaff($damager->getName(), $this->getName(), $data);
                    }
                }
            }
            $this->lastLastHit[$damager->getName()] = $this->lastHit[$damager->getName()];
            $this->lastHit[$damager->getName()] = [
                "distance" => $event->getVectorDistanceXZ(),
                "expected" => $baseAllowed
            ];
        }
    }

    private function getAllowedDistance() : float{
        return 3.3;
    }

}