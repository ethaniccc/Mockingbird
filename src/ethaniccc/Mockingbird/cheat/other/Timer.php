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

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;

class Timer extends Cheat implements StrictRequirements{

    /** @var array */
    private $playerBalance, $playerPreviousTimeDiff, $playerLastSentTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(20.0);
        $this->setRequiredPing(1000000);
    }

    public function onMove(MoveEvent $event) : void{
        $currentTick = $this->getServer()->getTick();
        $player = $event->getPlayer();
        $name = $player->getName();

        if(!isset($this->playerBalance[$name])){
            $this->playerBalance[$name] = 0;
        }
        if(!isset($this->playerLastSentTick[$name])){
            $this->playerLastSentTick[$name] = $currentTick;
            return;
        }

        $time = ($currentTick - $this->playerLastSentTick[$name]) * 50;
        if($this->getServer()->getTicksPerSecond() == 20){
            $this->playerBalance[$name] += 50;
            $this->playerBalance[$name] -= $time;
        }

        if(isset($this->playerPreviousTimeDiff[$name])){
            // the player decided not to move and not cause of lag.
            if($this->playerPreviousTimeDiff[$name] > 100 && ($time <= 100 && $time >= 50)){
                $this->playerBalance[$name] = 0;
            }
        }

        if($this->playerBalance[$name] >= 500){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            $this->playerBalance[$name] = 0;
        }

        $this->playerLastSentTick[$name] = $currentTick;
        $this->playerPreviousTimeDiff[$name] = $time;
    }

}