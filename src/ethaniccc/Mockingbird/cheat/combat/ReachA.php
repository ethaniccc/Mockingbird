<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;

class ReachA extends Cheat{

    /** @var array */
    private $boundingBoxes = [];
    /** @var array */
    private $lastTarget = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getPlayerHit();
        $name = $damager->getName();
        $this->lastTarget[$name] = $damaged;
        $ray = new Ray($damager->add(0, $damager->getEyeHeight()), $damager->getDirectionVector());
        if(!isset($this->boundingBoxes[$damaged->getName()])){
            return;
        }
        $boundingBoxes = $this->boundingBoxes[$damaged->getName()];
        if(count($boundingBoxes) !== 20){
            return;
        }
        $distances = [];
        foreach($boundingBoxes as $box){
            $collision = $box->collidesRay($ray, 0, 10);
            if($collision != -1){
                array_push($distances, $collision);
            }
        }
        if(count($distances) === 0){
            return;
        }
        $distance = min($distances);
        $max = $damager->isCreative() ? 6 : 3.1;
        if($distance > $max){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damager));
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        if(!isset($this->boundingBoxes[$player->getName()])){
            $this->boundingBoxes[$player->getName()] = [];
        }
        if(count($this->boundingBoxes[$player->getName()]) === 20){
            array_shift($this->boundingBoxes[$player->getName()]);
        }
        array_push($this->boundingBoxes[$player->getName()], AABB::fromPlayer($player));
    }

}