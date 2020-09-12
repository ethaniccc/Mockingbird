<?php

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
        if($event->getMode() === MoveEvent::MODE_NORMAL && $user->hasNoMotion() && (!$player->getAllowFlight() || !$player->isFlying() || $player->isSpectator())){
            if(($user = $this->getPlugin()->getUserManager()->get($player)) instanceof User){
                $distance = $event->getDistanceXZ();
                $deltaY = $event->getDistanceY();
                $acceleration = $deltaY - $user->getLastMoveDelta()->getY();
                if($user->getOffGroundTicks() >= 10
                && $distance > 0.1
                && ($deltaY == 0 || $acceleration == 0)
                && LevelUtils::getBlockUnder($player, 1) instanceof Air
	            && !$player->isFlying()
	            && !$player->getAllowFlight()
                && !$player->isSpectator()
                && !$player->getInventory()->getItemInHand()->hasEnchantment(\pocketmine\item\enchantment\Enchantment::RIPTIDE)){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 3){
                        $this->suppress($event);
                        $this->fail($player, $this->formatFailMessage($this->basicFailData($player)));
                    }
                } else {
                    $this->lowerPreVL($name, 0.5);
                }
            }
        }
    }

}