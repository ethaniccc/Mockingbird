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

namespace ethaniccc\Mockingbird\cheat\combat\reach;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $distances, $cooldown = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }

        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Living){
            return;
        }

        $name = $damager->getName();
        if(!isset($this->distances[$name])){
            $this->distances[$name] = [];
        }
        if(!isset($this->cooldown[$name])){
            $this->cooldown[$name] = $this->getServer()->getTick();
        } else {
            if($this->getServer()->getTick() - $this->cooldown[$name] >= $event->getAttackCooldown()){
                $this->cooldown[$name] = $this->getServer()->getTick();
            } else {
                return;
            }
        }

        $onGround = LevelUtils::isNearGround($damaged, -0.75);

        // we do a check for the distance from a ray from the player's eye height
        // to the edge of the player's hitbox.
        $ray = Ray::from($damager);
        $AABB = AABB::from($damaged);
        $distance = round($AABB->collidesRay($ray, 0, 10), 2);

        if($distance != -1){
            $expectedDist = $onGround ? 3.2 : 4.1;
            if($distance > $expectedDist){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 2.5){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), ["VL" => self::getCurrentViolations($name), "Dist" => $distance]);
                }
            } else {
                $this->lowerPreVL($name, 0.8);
            }
        }
    }

}