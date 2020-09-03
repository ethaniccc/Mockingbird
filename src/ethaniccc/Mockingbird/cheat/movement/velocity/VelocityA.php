<?php

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

    private $lastVertical, $ticksSinceSend = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $name = $entity->getName();
            $vertical = $event->getVector()->y;
            $this->lastVertical[$name] = $vertical;
            $this->ticksSinceSend[$name] = 0;
            $this->lowerPreVL($name, 0);
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        $attacked = isset($this->lastVertical[$name]) && isset($this->ticksSinceSend[$name]) && $player->isAlive();
        if($attacked){
            if(in_array($event->getMode(), [MoveEvent::MODE_TELEPORT, MoveEvent::MODE_RESET])){
                unset($this->lastVertical[$name]);
                unset($this->ticksSinceSend[$name]);
                return;
            }
            ++$this->ticksSinceSend[$name];
            $maxTicks = (int) ($player->getPing() / 50) + 5 + (20 - $this->getServer()->getTicksPerSecond());
            // changed threshold from 0.99 to 0.98 for a hope for less falses. will *sometimes* detect 0.99 linear velocity.
            if($this->ticksSinceSend[$name] <= $maxTicks && $event->getDistanceY() <= $this->lastVertical[$name] * 0.985
            && LevelUtils::getBlockAbove($player)->getId() === 0
            && !LevelUtils::isNearBlock($player,BlockIds::COBWEB)
            && !LevelUtils::isNearBlock($player,BlockIds::WATER)){
                $this->addPreVL($name);
            } else {
                if($this->getPreVL($name) >= $maxTicks){
                    $this->fail($player, "$name's vertical velocity was lower than normal");
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