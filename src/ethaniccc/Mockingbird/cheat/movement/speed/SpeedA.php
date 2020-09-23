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

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
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
        $expectedDistance = $user->getServerOnGround() ? $this->getSetting("max_speed_on_ground") : $this->getSetting("max_speed_off_ground");
        $onIce = $user->getServerOnGround() ? LevelUtils::getBlockUnder($player, 0.5) instanceof Ice : LevelUtils::getBlockUnder($player, 1.2) instanceof Ice;
        if($onIce){
            $expectedDistance *= 4 / 3;
        }
        if($player->getEffect(1) !== null){
            $expectedDistance *= 1 + (0.2 * ($player->getEffect(1)->getAmplifier() + 1));
        }
        if($distance > $expectedDistance
        && $user->timePassedSinceMotion(20)
        && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)){
            $this->addPreVL($name);
            if($this->getPreVL($name) >= 4){
                $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "d: $distance, eMD: $expectedDistance");
            }
        } else {
            $this->lowerPreVL($name, 0.85);
        }
    }

}