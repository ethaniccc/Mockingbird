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
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class SpeedA extends Cheat{

    /** @var array */
    private $wasPreviouslyInAir, $previouslyJumped, $previouslyHadEffect, $previouslyOnIce = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isFlying() || $player->getAllowFlight()){
            return;
        }

        if($event->getMode() === MoveEvent::MODE_NORMAL){
            $distance = $event->getDistanceXZ();
            if($this->previouslyHadEffect($name)){
                return;
            }
            if($this->previouslyOnIce($name)){
                return;
            }
            if(!$player->getLevel()->getBlock($player->asVector3()->add(0, 2, 0)) instanceof Air){
                return;
            }
            if(!$player->isOnGround()){
                $expectedDistance = 0.785;
                $this->wasPreviouslyInAir[$name] = $this->getServer()->getTick();
            } else {
                if($this->wasPreviouslyInAir($name) || $this->recentlyJumped($name)){
                    $expectedDistance = 0.785;
                } else {
                    $expectedDistance = 0.325;
                    foreach(LevelUtils::getSurroundingBlocks($player, 3) as $block){
                        if($block instanceof Slab || $block instanceof Stair){
                            $expectedDistance = 1;
                        }
                    }
                }
            }
            if($this->onIce($player)){
                $expectedDistance *= (4 / 3);
                $this->previouslyOnIce[$name] = $this->getServer()->getTick();
            }
            if($player->getEffect(1) !== null){
                $effectLevel = $player->getEffect(1)->getEffectLevel() + 1;
                $expectedDistance *= 1 + (0.2 * $effectLevel);
                $this->previouslyHadEffect[$name] = $this->getServer()->getTick();
            }
            if($distance > $expectedDistance){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 3){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
                $this->debugNotify("$name moved at a distance of $distance, expected around $expectedDistance");
            } else {
                $this->lowerPreVL($name);
            }
        }
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $name = $event->getPlayer()->getName();
        $this->previouslyJumped[$name] = $this->getServer()->getTick();
    }

    private function wasPreviouslyInAir(string $name) : bool{
        return isset($this->wasPreviouslyInAir[$name]) ? $this->getServer()->getTick() - $this->wasPreviouslyInAir[$name] <= 5 : false;
    }

    private function recentlyJumped(string $name) : bool{
        return isset($this->previouslyJumped[$name]) ? $this->getServer()->getTick() - $this->previouslyJumped[$name] <= 1 : false;
    }

    private function previouslyHadEffect(string $name) : bool{
        return isset($this->previouslyHadEffect[$name]) ? $this->getServer()->getTick() - $this->previouslyHadEffect[$name] <= 1 : false;
    }

    private function previouslyOnIce(string $name) : bool{
        return isset($this->previouslyOnIce[$name]) ? $this->getServer()->getTick() - $this->previouslyOnIce[$name] <= 10 : false;
    }

    private function onIce(Player $player) : bool{
        return $player->isOnGround() ? in_array($player->getLevel()->getBlock($player->asVector3()->subtract(0, 0.5, 0))->getId(), [ItemIds::ICE, ItemIds::PACKED_ICE, ItemIds::FROSTED_ICE]) : in_array($player->getLevel()->getBlock($player->asVector3()->subtract(0, 1.25, 0))->getId(), [ItemIds::ICE, ItemIds::PACKED_ICE, ItemIds::FROSTED_ICE]);
    }

}