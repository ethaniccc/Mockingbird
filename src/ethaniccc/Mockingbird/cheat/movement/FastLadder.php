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
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\BlockIds;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\Player;

class FastLadder extends Cheat implements StrictRequirements{

    private $hasJumped = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($event->getMode() !== MoveEvent::MODE_NORMAL){
            return;
        }

        if($player->isCreative()){
            return;
        }
        if($player->isFlying()){
            return;
        }

        if($this->hasRecentlyJumped($player)){
            return;
        }

        if(LevelUtils::isNearBlock($player, BlockIds::LADDER, 0.25)){
            $yDist = round($event->getDistanceY(), 1);
            if($yDist == 0) return;
            $expectedDist = 0.2;
            if($yDist > $expectedDist){
                if($yDist === 0.3){
                    // Player is spam jumping and PlayerJumpEvent not being triggered?
                    return;
                }
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        }
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $name = $event->getPlayer()->getName();
        $this->hasJumped[$name] = $this->getServer()->getTick();
    }

    private function hasRecentlyJumped(Player $player) : bool{
        return isset($this->hasJumped[$player->getName()]) ? $this->getServer()->getTick() - $this->hasJumped[$player->getName()] <= 20 : false;
    }

}