<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;

class Timer extends Cheat implements StrictRequirements{

    /** @var array */
    private $balance, $previousTimeDiff, $lastSentTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(20.0);
        $this->setRequiredPing(10000);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->balance[$name])){
            $this->balance[$name] = 0;
        }
        if(!isset($this->lastSentTick[$name])){
            $this->lastSentTick[$name] = $this->getServer()->getTick();
            return;
        }

        $time = ($this->getServer()->getTick() - $this->lastSentTick[$name]) * 50;
        $this->balance[$name] += 50;
        $this->balance[$name] -= $time;

        if(isset($this->previousTimeDiff[$name])){
            // the player decided not to move and not cause of lag.
            if($this->previousTimeDiff[$name] > 100 && ($time <= 100 && $time >= 50)){
                $this->balance[$name] = 0;
            }
        }

        if($this->balance[$name] >= 500){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            $this->balance[$name] = 0;
        }

        $this->lastSentTick[$name] = $this->getServer()->getTick();
        $this->previousTimeDiff[$name] = $time;
    }

}