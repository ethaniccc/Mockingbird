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
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class Hitbox extends Cheat{

    /** @var array */
    private $AABB = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }

        if(!isset($this->AABB[$damaged->getName()])){
            return;
        }

        if(count($this->AABB[$damaged->getName()]) < 30){
            return;
        }

        $estimatedHitTime = (microtime(true) * 1000) - $damager->getPing();
        foreach($this->AABB[$damaged->getName()] as $arrayInfo){
            $time = $arrayInfo["Time"];
            if(abs($estimatedHitTime - $time) < 20){
                $AABB = $arrayInfo["AABB"];
                $ray = new Ray($damager->add(0, $damager->getEyeHeight(), 0), $damager->getDirectionVector());
                $collision = $AABB->collidesRay($ray, 0, 15);
                if($collision == -1){
                    $this->addPreVL($damager->getName());
                    if($this->getPreVL($damager->getName()) >= 10){
                        $this->debugNotify("{$damager->getName()} has failed an experimental check for Hitbox - damager's ray did not collide with entity's hitbox.");
                        $this->lowerPreVL($damager->getName(), 0);
                    }
                } else {
                    $this->lowerPreVL($damager->getName());
                }
            }
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->AABB[$name])){
            $this->AABB[$name] = [];
        }
        if(count($this->AABB[$name]) === 40){
            array_shift($this->AABB[$name]);
        }
        $info = [
            // make microtime into ms
            "Time" => microtime(true) * 1000,
            "AABB" => AABB::from($player)
        ];
        array_push($this->AABB[$name], $info);
    }

}