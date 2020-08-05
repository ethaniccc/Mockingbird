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
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;

class MultiAura extends Cheat implements StrictRequirements{

    private $lastHit = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getPlayerHit();
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
                if($damaged->distance($this->getServer()->getPlayer($this->lastHit[$name]["Entity"])) > 3){
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