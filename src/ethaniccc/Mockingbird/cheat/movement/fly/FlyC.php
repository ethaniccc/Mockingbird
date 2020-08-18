<?php

namespace ethaniccc\Mockingbird\cheat\movement\fly;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\entity\Effect;

class FlyC extends Cheat{

    /** @var array */
    private $lastGroundLocation = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($event->getMode() === MoveEvent::MODE_NORMAL && ($player->getMotion()->x == 0 && $player->getMotion()->z == 0)){
            $to = $event->getTo();
            $from = $event->getFrom();

            $distX = $to->x - $from->x;
            $distY = $to->y - $from->y;
            $distZ = $to->z - $from->z;

            $horizontalDist = hypot($distX, $distZ);
            $moving = $horizontalDist > 0 || abs($distY) > 0;
            $onGround = LevelUtils::isNearGround($player);

            $effectLevel = $player->getEffect(Effect::JUMP_BOOST) ? 1 + $player->getEffect(Effect::JUMP_BOOST)->getAmplifier() : 0;
            if($moving && !$onGround && $distY > 0){
                $distanceGround = isset($this->lastGroundLocation[$name]) ? LevelUtils::getDistance($to, $this->lastGroundLocation[$name]) : 0;
                $threshold = $effectLevel > 0 ? 5 + (pow($effectLevel + 4.2, 2) / 16) : 5;
                if($distanceGround > $threshold){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 5){
                        $this->addViolation($name);
                        $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    }
                } else {
                    $this->lowerPreVL($name, 0);
                }
            } else {
                $this->lowerPreVL($name, 0);
                $this->lastGroundLocation[$name] = $to;
            }
        }
    }

}