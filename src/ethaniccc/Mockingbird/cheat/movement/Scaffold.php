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
        if($AABB === null){
            return;
        }
        $isLookingAtBlock = $AABB->collidesRay($ray, 0, 10) != -1;

        if(!$isLookingAtBlock && !$this->getPlugin()->getUserManager()->get($player)->isMobile()){
            $this->addPreVL($player->getName());
            if($this->getPreVL($player->getName()) >= 7){
                // still experimental so no suppression. plus, if this somehow falses, it may cause issues.
                $this->fail($player, "{$player->getName()} placed a block without looking at it");
            }
        } else {
            $this->lowerPreVL($player->getName(), 0);
        }
    }

}