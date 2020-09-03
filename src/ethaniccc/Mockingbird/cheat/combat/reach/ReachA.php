<?php

namespace ethaniccc\Mockingbird\cheat\combat\reach;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MathUtils;

class ReachA extends Cheat{

    private $cooldown, $hitInfo = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    /**
     * @param PlayerHitPlayerEvent $event
     * @priority LOWEST
     */
    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getPlayerHit();
        if($damager->isCreative() || $damager->isSpectator()){
            return;
        }
        $damagedUser = $this->getPlugin()->getUserManager()->get($damaged);
        $name = $damager->getName();
        $currentTick = $this->getServer()->getTick();
        if(!isset($this->cooldown[$name])){
            $this->cooldown[$name] = $currentTick;
        } else {
            if($currentTick - $this->cooldown[$name] >= $event->getAttackCoolDown()){
                $this->cooldown[$name] = $currentTick;
            } else {
                return;
            }
        }
        $estimatedLocation = $damagedUser->getLocationHistory()->getLocationRelativeToTime(MathUtils::getTimeMS() - $damager->getPing() - (50 * (1 + (20 - $this->getServer()->getTicksPerSecond()))));
        if($estimatedLocation === null){
            return;
        }
        $AABB = AABB::fromPosition($estimatedLocation);
        $this->hitInfo[$name] = [
            "Time" => $currentTick,
            "AABB" => $AABB
        ];
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(isset($this->hitInfo[$name])){
            if($this->getServer()->getTick() - $this->hitInfo[$name]["Time"] <= 10){
                $currentLocation = $event->getTo();
                $AABB = $this->hitInfo[$name]["AABB"];
                $distances = [];
                foreach($AABB->getCornerVectors() as $vector){
                    $distances[] = $currentLocation->distance($vector);
                }
                $distance = min($distances);
                if($distance > 3){
                    $this->debugNotify("$name hit a player from distance: $distance");
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 2){
                        $roundedDistance = round($distance, 2);
                        $this->fail($player, "$name hit a player at a distance of $roundedDistance");
                        $this->lowerPreVL($name, 0);
                    }
                } else {
                    $this->lowerPreVL($name);
                }
                unset($this->hitInfo[$name]);
            }
        }
    }

}