<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\player\PlayerJumpEvent;

class HighJump extends Cheat{

    private $ticksSinceJump = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $this->ticksSinceJump[$name] = 0;
        $this->lowerPreVL($name, 0);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $user->getName();
        $maxJump = $player->getJumpVelocity();
        $yDelta = $event->getDistanceY();
        $maxTicks = (int) ($player->getPing() / 100) + 2;
        if(isset($this->ticksSinceJump[$name])){
            ++$this->ticksSinceJump[$name];
            if($this->ticksSinceJump[$name] <= $maxTicks){
                if($yDelta > $maxJump && $user->timePassedSinceHit(40) && $event->getMode() === MoveEvent::MODE_NORMAL){
                    $this->addPreVL($name);
                } else {
                    $this->lowerPreVL($name, 0);
                }
            } else {
                if($this->getPreVL($name) >= $maxTicks){
                    $this->fail($player, "$name jumped too high");
                }
                $this->lowerPreVL($name, 0);
                unset($this->ticksSinceJump[$name]);
            }
        } else {
            $this->lowerPreVL($name, 0);
        }
    }

}