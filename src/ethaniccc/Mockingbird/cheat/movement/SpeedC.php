<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\Player;

class SpeedC extends Cheat{

    /** @var array */
    private $previousDistance, $onGroundTicks = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $onGround = $player->getLevel()->getBlock($player->asVector3()->subtract(0, 0.5001, 0))->getId() !== 0;
        $distance = $event->getDistanceXZ();

        if(!isset($this->previousDistance[$name])){
            $this->previousDistance[$name] = $distance;
            $this->onGroundTicks[$name] = 0;
            return;
        }

        if($onGround){
            ++$this->onGroundTicks[$name];
        } else {
            $this->onGroundTicks[$name] = 0;
        }

        if($this->onGroundTicks[$name] >= 40){
            $previousDistance = $this->previousDistance[$name];
            $prediction = ($previousDistance * 0.98 * LevelUtils::getBlockWalkingOn($player)->getFrictionFactor()) + (($player->isSprinting() ? 0.2806 : (!$player->isSneaking() ? 0.2158 : 0.064676)) * ($player->getEffect(1) !== null ? 1 + (($player->getEffect(1)->getAmplifier() + 1) * 0.2) : 1));
            $diff = ($distance - $prediction);
            if($diff > 0.01){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 5){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->lowerPreVL($name, 0.5);
                }
            } else {
                $this->lowerPreVL($name, 0.5);
            }
        }
    }

}