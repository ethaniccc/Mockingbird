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

use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\location\Vector4;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\user\User;

class ReachA extends Cheat{

    private $hitDistances = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $this->getPlugin()->getUserManager()->get($event->getDamager());
        $damaged = $this->getPlugin()->getUserManager()->get($event->getPlayerHit());

        if($damaged instanceof User && $damager instanceof User){
            // here we are trying to see the location the damager sees on their client
            $currentTime = MathUtils::getTimeMS();
            $estimatedTime = $currentTime - $damager->getPlayer()->getPing();
            $estimatedLocation = $damaged->getLocationHistory()->getLocationRelativeToTime($estimatedTime);
            if($estimatedLocation instanceof Vector4){
                $AABB = AABB::fromPosition($estimatedLocation);
                $ray = Ray::from($damager->getPlayer());
                $distance = $AABB->collidesRay($ray, 0, 10);
                if($distance != -1){
                    if(!isset($this->hitDistances[$damager->getName()])){
                        $this->hitDistances[$damager->getName()] = [];
                    }
                    if(count($this->hitDistances[$damager->getName()]) === 20){
                        array_shift($this->hitDistances[$damager->getName()]);
                    }
                    $this->hitDistances[$damager->getName()][] = $distance;
                    $averageDistance = MathUtils::getAverage($this->hitDistances[$damager->getName()]);
                    $expectedDistance = 4.1;
                    if($averageDistance > $expectedDistance && $distance > $expectedDistance){
                        $roundedDistance = round($distance, 2);
                        $this->fail($damager->getPlayer(), "{$damager->getName()} hit a player at $roundedDistance, expected $expectedDistance");
                    }
                }
            }
        }
    }

}