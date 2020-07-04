<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\Air;
use pocketmine\event\player\PlayerMoveEvent;

class Fly extends Cheat{

    private $ticksOffGround = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $distance = LevelUtils::getMoveDistance($event->getTo()->asVector3(), $event->getFrom()->asVector3(), LevelUtils::MODE_Y);
        if($distance > 1){
            // Player is probably falling.
            if(isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
            return;
        }
        $blocksAround = LevelUtils::getSurroundingBlocks($player);
        $continue = true;
        foreach($blocksAround as $block){
            if(!$block instanceof Air){
                $continue = false;
            }
        }
        if($continue && !$player->isOnGround()){
            if(!isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
            $this->ticksOffGround[$name] += 1;
            if($this->ticksOffGround[$name] > 100){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        } else {
            if(isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
        }
    }

}