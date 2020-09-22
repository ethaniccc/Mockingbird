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
use ethaniccc\Mockingbird\utils\user\User;;
use pocketmine\item\ItemIds;

class FlyA extends Cheat{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if($user instanceof User && $event->getMode() === MoveEvent::MODE_NORMAL && $player->isAlive()){
            $lastYDelta = $user->getLastMoveDelta()->y;
            $yDelta = $user->getMoveDelta()->y;
            $predictedDelta = ($lastYDelta - 0.08) * 0.980000019073486;
            if($user->getOffGroundTicks() >= 5 && abs($predictedDelta) > 0.05 && $player->getArmorInventory()->getChestplate()->getId() !== ItemIds::ELYTRA){
                if(abs($predictedDelta - $yDelta) >= $this->getSetting("max_breach")
                && $user->timePassedSinceDamage(20)
                && $user->timePassedSinceTeleport(3)
                && $user->timePassedSinceJoin(40)
                && !$player->isFlying()
                && !$player->getAllowFlight()
                && !$player->isSpectator()
                && $event->getMode() === MoveEvent::MODE_NORMAL
                && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)
                && $user->getCurrentLocation()->getY() > 0){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 3){
                        $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "$name: yD: $yDelta, pD: $predictedDelta");
                    }
                } else {
                    $this->lowerPreVL($name);
                }
            }
        }
    }

}