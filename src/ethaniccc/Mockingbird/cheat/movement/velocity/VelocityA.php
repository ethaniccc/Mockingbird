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

namespace ethaniccc\Mockingbird\cheat\movement\velocity;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\BlockIds;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\Player;
use pocketmine\event\player\PlayerDeathEvent;

class VelocityA extends Cheat{

    private $lastVertical, $ticksSinceSend, $susLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $user = $this->getPlugin()->getUserManager()->get($entity);
            if($user->timePassedSinceTeleport(5) && $event->getVector()->getY() > 0 && !isset($this->ticksSinceSend[$entity->getName()])){
                $name = $entity->getName();
                $vertical = $event->getVector()->y;
                $this->lastVertical[$name] = $vertical;
                $this->ticksSinceSend[$name] = 0;
                $this->lowerPreVL($name, 0);
            }
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $player->getName();

        if(!isset($this->susLevel[$name])){
            $this->susLevel[$name] = 0;
        }

        $attacked = isset($this->lastVertical[$name]) && isset($this->ticksSinceSend[$name]) && $player->isAlive();
        if($attacked){
            if(in_array($event->getMode(), [MoveEvent::MODE_TELEPORT, MoveEvent::MODE_RESET])){
                unset($this->lastVertical[$name]);
                unset($this->ticksSinceSend[$name]);
                return;
            }
            ++$this->ticksSinceSend[$name];
            $maxTicks = (int) ($player->getPing() / 50) + 2;
            if($this->ticksSinceSend[$name] <= $maxTicks && $event->getDistanceY() < $this->lastVertical[$name] * $this->getSetting("percentage")
            && !LevelUtils::hasBlockAbove($user)
            && !LevelUtils::isNearBlock($user ,BlockIds::COBWEB)
            && !LevelUtils::isNearBlock($user, BlockIds::WATER)){
                $this->addPreVL($name);
            } else {
                if($this->getPreVL($name) >= $maxTicks){
                    ++$this->susLevel[$name];
                    if($this->susLevel[$name] >= 4){
                        $this->fail($player, null, $this->formatFailMessage($this->basicFailData($player)));
                    }
                } else {
                    $this->susLevel[$name] = 0;
                }
                $this->lowerPreVL($name, 0);
                unset($this->lastVertical[$name]);
                unset($this->ticksSinceSend[$name]);
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        unset($this->ticksSinceSend[$name]);
        unset($this->lastVertical[$name]);
    }

}