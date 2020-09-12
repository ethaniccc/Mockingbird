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

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
        $this->setRequiredTPS(19.0);
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
            if(($timeDiff = microtime(true) - $this->lastHit[$name]["Time"]) < $this->getSetting("time")){
                if($damaged->distance($this->getServer()->getPlayer($this->lastHit[$name]["Entity"])) > $this->getSetting("entity_distance")){
                    $this->suppress($event);
                    $this->fail($damager, $this->formatFailMessage($this->basicFailData($damager)));
                }
            }
        }

        $this->lastHit[$name] = [
            "Time" => microtime(true),
            "Entity" => $entityName,
        ];
    }

}