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
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\user\User;
use pocketmine\block\Air;
use pocketmine\block\BlockIds;
use pocketmine\item\ItemIds;

class FlyA extends Cheat implements StrictRequirements{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if($user instanceof User && $event->getMode() === MoveEvent::MODE_NORMAL){
            $lastYDelta = $user->getLastMoveDelta()->y;
            $yDelta = $user->getMoveDelta()->y;
            $predictedDelta = ($lastYDelta - 0.08) * 0.980000019073486;
            if($user->getOffGroundTicks() >= 10 && abs($predictedDelta) > 0.05 && $player->getArmorInventory()->getChestplate()->getId() !== ItemIds::ELYTRA){
                if(!MathUtils::isRoughlyEqual($yDelta, $predictedDelta)
                && $user->timePassedSinceDamage(10)
                && $user->timePassedSinceJoin(40)
                && $user->timePassedSinceHit(20)
                && !LevelUtils::isNearBlock($player, BlockIds::COBWEB, 2)
                && LevelUtils::getBlockUnder($player, 1) instanceof Air
                && !$player->isFlying()
                && !$player->getAllowFlight()
                && !$player->isSpectator()
                && $event->getMode() === MoveEvent::MODE_NORMAL
                && $player->isAlive()){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 10){
                        $this->lowerPreVL($name, 0.75);
                        $this->suppress($event);
                        $this->fail($player, "$name's Y distance was not as expected", [], "$name's Y distance was $yDelta, predicted $predictedDelta");
                    }
                } else {
                    $this->lowerPreVL($name);
                }
            }
        }
    }

}