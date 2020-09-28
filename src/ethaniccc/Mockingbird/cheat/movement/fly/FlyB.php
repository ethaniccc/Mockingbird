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
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\user\User;
use pocketmine\block\Air;
use pocketmine\math\Vector3;

class FlyB extends Cheat{


    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $player->getName();
        if($event->getMode() === MoveEvent::MODE_NORMAL && $user->hasNoMotion() && !$player->getAllowFlight() && !$player->isFlying() && !$player->isSpectator()){
            if(($user = $this->getPlugin()->getUserManager()->get($player)) instanceof User){
                $distance = $event->getDistanceXZ();
                $deltaY = $event->getDistanceY();
                $acceleration = $deltaY - $user->getLastMoveDelta()->getY();
                if($user->getOffGroundTicks() >= 5
                && $distance > 0.1
                && ($deltaY == 0 || $acceleration == 0)
	            && !$player->isFlying()
	            && !$player->getAllowFlight()
                && !$player->isSpectator()
                && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)
                && $user->getCurrentLocation()->getY() > 0
                && $user->timePassedSinceMotion(10)){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 3){
                        $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)));
                    }
                } else {
                    $this->lowerPreVL($name, 0.5);
                }
            }
        }
    }

}