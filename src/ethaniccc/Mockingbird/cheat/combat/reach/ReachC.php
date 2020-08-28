<?php

namespace ethaniccc\Mockingbird\cheat\combat\reach;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MathUtils;

class ReachC extends Cheat{

    private $cooldown = [];

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
        $damagerUser = $this->getPlugin()->getUserManager()->get($damager);
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
        $distances = [];
        $AABB = AABB::fromPosition($estimatedLocation);
        foreach($AABB->getCornerVectors() as $vector){
            $distances[] = $vector->distance($damager->add(0, $damager->getEyeHeight(), 0)->add($damagerUser->getMoveDelta()));
        }
        $distance = min($distances);
        if($distance > 3){
            $this->debugNotify("Distance was higher than expected for $name: $distance");
            $this->addPreVL($name);
            if($this->getPreVL($name) >= 1.25){
                $roundedDistance = round($distance, 2);
                $this->fail($damager, "$name hit an {$damaged->getName()} from $roundedDistance blocks");
            }
        } else {
            $this->lowerPreVL($name, 0.5);
        }
    }

}