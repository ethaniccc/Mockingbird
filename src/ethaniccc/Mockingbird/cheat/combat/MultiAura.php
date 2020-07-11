<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\Player;

class MultiAura extends Cheat implements StrictRequirements{

    private $lastHit = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damager instanceof Player){
            return;
        }
        if(!$damaged instanceof Player){
            return;
        }
        $name = $damager->getName();
        $entityName = $damaged->getName();
        if(!isset($this->lastHit[$name])){
            $this->lastHit[$name] = [
                "Time" => microtime(true),
                "Entity" => $entityName,
            ];
            return;
        }

        if($damaged->getName() != $this->lastHit[$name]["Entity"]){
            if(microtime(true) - $this->lastHit[$name]["Time"] < 0.01){
                $timeDiff = microtime(true) - $this->lastHit[$name]["Time"];
                if($damaged->distance($this->getServer()->getPlayer($this->lastHit[$name]["Entity"])) > 3.5){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damager));
                }
            }
        }

        $this->lastHit[$name] = [
            "Time" => microtime(true),
            "Entity" => $entityName,
        ];
    }

}