<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;

class Phase extends Cheat{

    /** @var array */
    private $moveTicks, $inTicks = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if(in_array($player->getGamemode(), [1, 3])){
            return;
        }

        if(!isset($this->moveTicks[$name])){
            $this->moveTicks[$name] = 0;
            $this->inTicks[$name] = 0;
        }

        $inBlock = $player->isInsideOfSolid();
        if($inBlock){
            ++$this->inTicks[$name];
        } else {
            $this->inTicks[$name] = 0;
            $this->moveTicks[$name] = 0;
        }

        $distance = $event->getDistanceXZ();
        if($distance > 0.1){
            ++$this->moveTicks[$name];
        }

        if($this->moveTicks[$name] > 10 && $this->inTicks[$name] > 10){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
        }
    }

}