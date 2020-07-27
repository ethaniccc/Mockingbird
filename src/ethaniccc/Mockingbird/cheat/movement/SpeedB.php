<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\utils\LevelUtils;

class SpeedB extends Cheat{

    /** @var array */
    private $lastDist, $lastMovedTick, $ticksSprinting, $previouslyOnGround = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $onGround = $player->isOnGround();
        $distance = $event->getDistanceXZ();
        if(!isset($this->lastDist[$name])){
            $this->previouslyOnGround[$name] = $onGround;
            $this->lastDist[$name] = $distance;
            return;
        }

        if(!isset($this->ticksSprinting[$name])){
            $this->ticksSprinting[$name] = 0;
        }

        if($player->isSprinting() || $distance > 0.275){
            ++$this->ticksSprinting[$name];
        } else {
            $this->ticksSprinting[$name] = 0;
        }

        if($this->getServer()->getTick() - $this->lastMovedTick[$name] <= 1){
            $previousDistance = $this->lastDist[$name];
            $previouslyOnGround = $this->previouslyOnGround[$name];

            $friction = 0.98;
            $shiftedLastDistance = $previousDistance * $friction;

            $diff = $distance - $shiftedLastDistance;
            $diffScaled = $diff * 130;

            $hitboxCollidesWithBlock = false;
            foreach(LevelUtils::getSurroundingBlocks($player, 3) as $block){
                if($block->getId() !== 0){
                    if($block->collidesWithBB($player->getBoundingBox()->expand(0.1, 0.1, 0.1))){
                        $hitboxCollidesWithBlock = true;
                    }
                }
            }

            if(!$onGround && !$previouslyOnGround && !$hitboxCollidesWithBlock && $this->ticksSprinting[$name] > 10){
                if($diffScaled >= 1){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 5){
                        $this->addViolation($name);
                        $this->notify("$name failed a check for SpeedB");
                        $this->lowerPreVL($name, 0);
                    }
                } else {
                    $this->lowerPreVL($name, 0.25);
                }
            }
        }

        $this->lastDist[$name] = $distance;
        $this->previouslyOnGround[$name] = $onGround;
        $this->lastMovedTick[$name] = $this->getServer()->getTick();
    }

}