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
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\Air;
use pocketmine\block\Ice;

class SpeedA extends Cheat{

    private const MAX_ONGROUND = 0.375;
    private const MAX_OFFGROUND = 0.78;

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $user->getName();
        $distance = $event->getDistanceXZ();
        if($player->isFlying() || $event->getMode() !== MoveEvent::MODE_NORMAL){
            return;
        }
        if(!LevelUtils::getBlockAbove($player) instanceof Air){
            return;
        }
        $expectedDistance = $user->getServerOnGround() ? self::MAX_ONGROUND : self::MAX_OFFGROUND;
        $onIce = $user->getServerOnGround() ? LevelUtils::getBlockUnder($player, 0.5) instanceof Ice : LevelUtils::getBlockUnder($player, 1.2) instanceof Ice;
        if($onIce){
            $expectedDistance *= 4 / 3;
        }
        if($player->getEffect(1) !== null){
            $expectedDistance *= 1 + (0.2 * ($player->getEffect(1)->getAmplifier() + 1));
        }
        if($distance > $expectedDistance
        && $user->hasNoMotion()
        && $user->timePassedSinceHit(20)
        && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)){
            $this->addPreVL($name);
            if($this->getPreVL($name) >= 6){
                $this->suppress($event);
                $this->fail($player, "$name moved faster than normal");
            }
        } else {
            $this->lowerPreVL($name, 0.85);
        }
    }

}