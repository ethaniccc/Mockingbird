<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;

class Phase extends Cheat{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
        $this->setEnabled(false);
    }

    public function onMove(MoveEvent $event) : void{
        // FiXME: This is a shitty check
        $player = $event->getPlayer();
        $name = $player->getName();
        $user = $this->getPlugin()->getUserManager()->get($player);
        if($user->getCurrentLocation() === null){
            return;
        }
        $AABB = AABB::fromPosition($user->getCurrentLocation())->expand(-0.1, 0, -0.1);
        $AABB->maxY -= 0.1;
        $AABB->minY += 0.05;
        $collidesWithBlock = count($player->getLevel()->getCollisionBlocks($AABB, false)) > 0;
        if($collidesWithBlock
        && $user->timePassedSinceTeleport(5)
        && $user->getMoveDistance() > 0.01
        && $event->getMode() === MoveEvent::MODE_NORMAL
        && $event->getDistanceY() < 0.355){
            $this->fail($player, $event, "$name blootant cheater smh smh", [], "$name, mD: {$event->getDistanceXZ()}, yD: {$event->getDistanceY()}");
        }
    }

}