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
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class Speed extends Cheat{

    private $lastDistance = [];
    private $lastTime = [];

    public function __construct(Plugin $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(PlayerMoveEvent $event) : void{
        /* My check: https://github.com/Bavfalcon9/Mavoric/pull/72/commits/0c59b37a24ac9c8362f4a5c003dfee02a5cc849d */
        $player = $event->getPlayer();

        $from = $event->getFrom();
        $to = $event->getTo();
        
        $dX = $to->x - $from->x;
        $dZ = $to->z - $from->z;

        if(!isset($this->lastDistance[$player->getName()])) {
            $this->lastDistance[$player->getName()] = [
                0 => $dX,
                1 => $dZ
            ];
            return;
        }
        
        if(!isset($this->lastTime[$player->getName()])) $this->lastTime[$player->getName()] = microtime(true);

        $expected = 0.82567;
        if($player->getEffect(1) !== null){
            if($player->getEffect(1)->getEffectLevel() != 0){
                $expected = 0.82567 + ($player->getEffect(1)->getEffectLevel() * 0.2) * 0.82567;
            }
        }

        // if the last moved tick was 2 ticks ago
        $currentTime = microtime(true);
        $time = $currentTime - $this->lastTime[$player->getName()];
        $ticks = round($time * 20, 2);
        if($ticks >= 2){
            if($player->getEffect(1) !== null){
                if($player->getEffect(1)->getEffectLevel() != 0){
                    $expected = 0.82567 + ($player->getEffect(1)->getEffectLevel() * 0.2) * 0.82567;
                }
            } else {
                $expected = 0.82567 * $ticks;
            }
        }
        
        if($dX > $expected || $dX < -$expected) {
            if($this->lastDistance[$player->getName()][0] >= $expected){
                $this->addViolation($player->getName());
                $data = [
                    "VL" => $this->getCurrentViolations($player->getName()),
                    "Ping" => $player->getPing(),
                    "TPS" => $this->getServer()->getTicksPerSecond()
                ];
                $this->notifyStaff($player->getName(), $this->getName(), $data);
            }
        }

        if($dZ > $expected || $dZ < -$expected) {
            if($this->lastDistance[$player->getName()][1] >= $expected){
                $this->addViolation($player->getName());
                $data = [
                    "VL" => $this->getCurrentViolations($player->getName()),
                    "Ping" => $player->getPing(),
                    "TPS" => $this->getServer()->getTicksPerSecond()
                ];
                $this->notifyStaff($player->getName(), $this->getName(), $data);
            }
        }

        $this->lastDistance[$player->getName()] = [
            0 => $dX,
            1 => $dZ
        ];

        unset($this->lastTime[$player->getName()]);
        $this->lastTime[$player->getName()] = microtime(true);
    }

}