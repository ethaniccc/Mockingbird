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

use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $attacked, $entityHit, $distances, $lastMoved = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }

        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }

        $name = $damager->getName();

        $this->attacked[$name] = true;
        $this->entityHit[$name] = $damaged->getName();
    }

    /**
     * @param MoveEvent $event
     * This reach check still does not work well with mobile players due to
     * the way the check is done.
     * TODO: Add a ReachB check to work with mobile players.
     */
    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->entityHit[$name])){
            $this->lastMoved[$name] = microtime(true) * 1000;
            return;
        }
        if(!isset($this->attacked[$name])){
            $this->attacked[$name] = false;
        }
        if(!isset($this->distances[$name])){
            $this->distances[$name] = [];
        }

        if($this->attacked[$name]){
            if(isset($this->distances[$name])){
                if(count($this->distances[$name]) >= 10){
                    array_shift($this->distances[$name]);
                }
            }
            if(!isset($this->lastMoved[$name]) || (microtime(true) * 1000) - $this->lastMoved[$name] <= 500){
                $this->attacked[$name] = false;
                $playerHit = $this->getServer()->getPlayer($this->entityHit[$name]);
                if($playerHit === null){
                    $this->lastMoved[$name] = microtime(true) * 1000;
                    return;
                }
                // we do a check for the distance from a ray from the player's eye height
                // to the edge of the player's hitbox.
                $ray = Ray::from($player);
                $distance = AABB::from($playerHit)->collidesRay($ray, 0, 10);
                if($distance != -1){
                    $this->distances[$name][] = $distance;
                }

                if(count($this->distances[$name]) >= 10){
                    $averageDist = MathUtils::getAverage($this->distances[$name]);
                    $expectedDistance = $player->isCreative() ? (LevelUtils::isNearGround($playerHit, 1) ? 5.1 : 6) : (LevelUtils::isNearGround($playerHit, 1) ? 3.1 : 4.15);
                    if($averageDist > 3.25 && $distance > $expectedDistance){
                        $this->addPreVL($name);
                        if($this->getPreVL($name) >= 3){
                            $this->addViolation($name);
                            $this->notifyStaff($name, $this->getName(), ["VL" => self::getCurrentViolations($name), "Dist" => round($distance, 3)]);
                        }
                    } else {
                        $this->lowerPreVL($name, 0.8);
                    }
                }
            }
        }

        $this->lastMoved[$name] = microtime(true) * 1000;
    }

}