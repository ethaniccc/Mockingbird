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

namespace ethaniccc\Mockingbird\cheat\movement\fly;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;

class FlyC extends Cheat{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if($user->getOffGroundTicks() >= 5
        && !$player->isFlying()
        && !$player->getAllowFlight()){
            $yDelta = $user->getMoveDelta()->getY();
            $lastYDelta = $user->getLastMoveDelta()->getY();
            // FlyA can solve a client just setting their motion Y to 0 bypassing this
            if(($equalness = abs($yDelta - $lastYDelta)) <= 1E-10
            && abs($yDelta) > 0 && abs($lastYDelta) > 0){
                // this type of motion is invalid since the current Y distance can't be the same as the previous one
                $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "$name: yD: $yDelta, lyD: $lastYDelta, e: $equalness");
            }
        }
    }

}