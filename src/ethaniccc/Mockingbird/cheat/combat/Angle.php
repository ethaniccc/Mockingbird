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
~ Made by @ethaniccc idot && BlackJack </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;

class Angle extends Cheat{

    private $lastHitTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $name = $damager->getName();

        if(isset($this->lastHitTick[$name])){
            if($this->getServer()->getTick() - $this->lastHitTick[$name] >= $event->getAttackCoolDown()){
                $this->lastHitTick[$name] = $this->getServer()->getTick();
            } else {
                return;
            }
        } else {
            $this->lastHitTick[$name] = $this->getServer()->getTick();
        }
        if($event->getAngle() > 140 && LevelUtils::getDistance($damager, $event->getPlayerHit()) > 1){
            // TODO Angle bug hack: safe check
            // $this->fail($damager, "Hit another entity at an angle of {$event->getAngle()}");
            $this->debugNotify("$name hit another entity at an angle of {$event->getAngle()}");
        }
    }

}
