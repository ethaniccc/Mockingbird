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

namespace ethaniccc\Mockingbird\cheat\movement\speed;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\MathUtils;

class SpeedB extends Cheat{

    /** @var array */
    private $lastDist, $lastMovedTick, $ticksSprinting, $previouslyOnGround, $movements = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $onGround = $player->isOnGround();
        $distance = $event->getDistanceXZ();

        if($event->getMode() !== MoveEvent::MODE_NORMAL){
            return;
        }

        if($player->isFlying() || $player->getAllowFlight()){
            return;
        }

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

        if(!isset($this->lastMovedTick[$name])){
            $this->lastMovedTick[$name] = $this->getServer()->getTick();
            return;
        }

        if($this->getServer()->getTick() - $this->lastMovedTick[$name] <= 1){
            $previousDistance = $this->lastDist[$name];
            $previouslyOnGround = $this->previouslyOnGround[$name];

            if(!$onGround && !$previouslyOnGround && $this->ticksSprinting[$name] > 10){
                $approximateFriction = 0.99;
                $expectedDistance = $previousDistance * $approximateFriction;
                $distanceDiff = round($distance - $expectedDistance, 5);
                if(!isset($this->movements[$name])){
                    $this->movements[$name] = [];
                }
                if(count($this->movements[$name]) === 20){
                    array_shift($this->movements[$name]);
                }
                array_push($this->movements[$name], $distanceDiff);
                if(count($this->movements[$name]) > 15){
                    $speedDeviation = MathUtils::getDeviation($this->movements[$name]);
                    if($speedDeviation < 0.0001){
                        $this->addViolation($name);
                        $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    }
                }
            }
        }

        $this->lastDist[$name] = $distance;
        $this->previouslyOnGround[$name] = $onGround;
        $this->lastMovedTick[$name] = $this->getServer()->getTick();
    }

}