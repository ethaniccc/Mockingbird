<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;

class NoFall extends Cheat{

    private $lastOnGround, $lastLastOnGround = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        $onGround = LevelUtils::isNearGround($player);
        if(!isset($this->lastOnGround[$name])){
            $this->lastOnGround[$name] = $onGround;
            return;
        }
        if(!isset($this->lastLastOnGround[$name])){
            $this->lastLastOnGround[$name] = $onGround;
            return;
        }
        $lastOnGround = $this->lastOnGround[$name];
        $lastLastOnGround = $this->lastLastOnGround[$name];

        if(!$onGround && !$lastOnGround && !$lastLastOnGround){
            if($event->onGround()){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 3){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->lowerPreVL($name, 0.5);
                }
            } else {
                $this->lowerPreVL($name, 0);
            }
        }

        $this->lastOnGround[$name] = $onGround;
        $this->lastLastOnGround[$name] = $lastOnGround;
    }

}