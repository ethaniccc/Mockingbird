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

class SpeedB extends Cheat{

    private $lastEqualness = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if(!$user->getServerOnGround() && $event->getMode() === MoveEvent::MODE_NORMAL
        && !$player->isFlying() && $user->hasNoMotion()){
            $name = $user->getName();
            $currentMoveDelta = $user->getMoveDistance();
            $lastMoveDelta = $user->getLastMoveDistance();
            // hmmm 0.91 is more accurate than 0.98 (<- player drag)
            $expectedMoveDelta = $lastMoveDelta * 0.91 + 0.026;
            $equalness = $currentMoveDelta - $expectedMoveDelta;
            if(!isset($this->lastEqualness[$name])){
                $this->lastEqualness[$name] = $equalness;
                return;
            }
            if($equalness > $this->getSetting("max_breach")
            && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)
            && $user->getCurrentLocation()->getY() > 0){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 3){
                    $this->lowerPreVL($name, 2 / 3);
                    $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "$name's friction was off by $equalness, last equalness was {$this->lastEqualness[$name]}");
                }
            } else {
                $this->lowerPreVL($name, 0);
            }
            $this->lastEqualness[$name] = $equalness;
        }
    }

}