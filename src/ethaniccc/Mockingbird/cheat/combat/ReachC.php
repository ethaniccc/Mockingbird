<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachC extends Cheat{

    /** @var array */
    private $hitboxes = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $currentTime = microtime(true);
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }
        if(isset($this->hitboxes[$damaged->getName()])){
            if(count($this->hitboxes[$damaged->getName()]) >= 10){
                $estimatedTime = $currentTime - $damager->getPing();
                $correctInfo = null;
                foreach($this->hitboxes[$damaged->getName()] as $arrayInfo){
                    $time = $arrayInfo["Time"];
                    if(abs($time - $estimatedTime) <= 0.5){
                        if($correctInfo === null){
                            $correctInfo = $arrayInfo;
                        } else {
                            if(abs($time - $estimatedTime) < abs($correctInfo["time"] - $estimatedTime)){
                                $correctInfo = $arrayInfo;
                            }
                        }
                    }
                }
                $ray = new Ray($damager->add(0, $damager->getEyeHeight(), 0), $damager->getDirectionVector());
                $AABB = $correctInfo["AABB"];
                $distance = $AABB->collidesRay($ray, 0, 10);
                if($distance > 3.05){
                    $this->addPreVL($damager->getName());
                    if($this->getPreVL($damager->getName()) >= 2){
                        $this->notify($damager->getName() . " failed a check for ReachC.");
                        $this->lowerPreVL($damager->getName(), 0.5);
                    }
                } else {
                    $this->lowerPreVL($damager->getName(), 0.95);
                }
            }
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->entityBaseTick();
        if(!isset($this->hitboxes[$name])){
            $this->hitboxes[$name] = [];
        }
        // get 2 seconds (40 ticks) worth of AABB's of the player.
        if(count($this->hitboxes[$name]) === 40){
            array_shift($this->hitboxes[$name]);
        }
        array_push($this->hitboxes[$name], ["Time" => microtime(true), "AABB" => AABB::fromPlayer($player)]);
    }

}