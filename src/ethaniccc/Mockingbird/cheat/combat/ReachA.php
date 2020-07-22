<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $boundingBoxes = [];
    /** @var array */
    private $lastTarget = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }
        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }
        $name = $damager->getName();
        $this->lastTarget[$name] = $damaged;
        $ray = new Ray($damager->add(0, $damager->getEyeHeight()), $damager->getDirectionVector());
        if(!isset($this->boundingBoxes[$damaged->getName()])){
            return;
        }
        $boundingBoxes = $this->boundingBoxes[$damaged->getName()];
        $distances = [];
        foreach($boundingBoxes as $box){
            array_push($distances, $box->collidesRay($ray, 0, 10));
        }
        if(count($distances) === 0){
            return;
        }
        $this->getServer()->broadcastMessage(implode(", ", $distances));
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        if(!isset($this->boundingBoxes[$player->getName()])){
            $this->boundingBoxes[$player->getName()] = [];
        }
        foreach($this->lastTarget as $target){
            if($player->getName() === $target->getName()){
                if(count($this->boundingBoxes[$player->getName()]) === 20){
                    array_shift($this->boundingBoxes[$player->getName()]);
                }
                array_push($this->boundingBoxes[$player->getName()], AABB::fromPlayer($player));
            }
        }
    }

}