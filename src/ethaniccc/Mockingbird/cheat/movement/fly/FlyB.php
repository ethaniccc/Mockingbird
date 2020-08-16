<?php

namespace ethaniccc\Mockingbird\cheat\movement\fly;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\math\Vector3;

class FlyB extends Cheat{

    /** @var array */
    private $offGroundTicks, $lastYDiff = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if($event->getMode() === MoveEvent::MODE_NORMAL && (new Vector3(0, 0, 0))->distance($player->getMotion()) == 0 && (!$player->getAllowFlight() || !$player->isFlying())){

            $to = $event->getTo();
            $from = $event->getFrom();

            $distX = $to->x - $from->x;
            $distY = $to->y - $from->y;
            $distZ = $to->z - $from->z;

            $horizontalDist = hypot($distX, $distZ);
            $acceleration = abs($distY - ($this->lastYDiff[$name] ?? $distY - 0.001));

            if(!LevelUtils::isNearGround($player)){
                if(!isset($this->offGroundTicks[$name])){
                    $this->offGroundTicks[$name] = 0;
                }
                ++$this->offGroundTicks[$name];

                if($this->offGroundTicks[$name] >= 10 && $horizontalDist > 0.1 && ($distY == 0 || $acceleration == 0)){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 3){
                        $this->addViolation($name);
                        $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    }
                } else {
                    $this->lowerPreVL($name, 0.85);
                }
            } else {
                $this->offGroundTicks[$name] = 0;
                $this->lowerPreVL($name, 0);
            }

            $this->lastYDiff[$name] = $distY;
        }
    }

}