<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\event\block\BlockPlaceEvent;

class Scaffold extends Cheat{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onBlockPlace(BlockPlaceEvent $event) : void{
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $ray = Ray::from($player);
        $AABB = AABB::fromBlock($block);
        $isLookingAtBlock = $AABB->collidesRay($ray, 0, 10) != -1;

        if(!$isLookingAtBlock){
            $this->addPreVL($player->getName());
            if($this->getPreVL($player->getName()) >= 10){
                $this->addViolation($player->getName());
                $this->notifyStaff($player->getName(), $this->getName(), $this->genericAlertData($player));
                $this->debugNotify("{$player->getName()}'s Ray did not collide with the bounding box of the block placed.");
            }
        } else {
            $this->lowerPreVL($player->getName(), 0);
        }
    }

}