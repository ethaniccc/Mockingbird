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

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\player\PlayerJumpEvent;

class HighJump extends Cheat{

    private $ticksSinceJump = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings = null){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $this->ticksSinceJump[$name] = 0;
        $this->lowerPreVL($name, 0);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if(isset($this->ticksSinceJump[$name])){
            ++$this->ticksSinceJump[$name];
            if($this->ticksSinceJump[$name] === 3){
                $estimatedYDelta = ((($player->getJumpVelocity() - 0.08) * 0.980000019073486) - 0.08) * 0.980000019073486;
                $yDelta = $user->getMoveDelta()->getY();
                $equalness = $yDelta - $estimatedYDelta;
                if($equalness > 0.1 && !$user->getServerOnGround()){
                    $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)));
                }
                unset($this->ticksSinceJump[$name]);
            }
        }
    }

}