<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $AABB = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * This reach check WILL NOT WORK FOR MOBILE PLAYERS.
     * The reason of this being it uses a ray trace to determine
     * the distance from the point of the damager's eye height to the
     * bounding box of the damaged entity. This method also depends on the
     * direction vector of the player. Therefore if a mobile player is NOT using
     * split controls, this check is ineffective.
     * TODO: Make a ReachB check for mobile players.
     */
    public function onHit(EntityDamageByEntityEvent $event) : void{

        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }
        
        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }

        $damagedName = $damaged->getName();
        $damagerName = $damager->getName();

        if(!isset($this->AABB[$damagedName])){
            return;
        }
        $estimatedHitTime = (microtime(true) * 1000) - $damager->getPing();
        foreach($this->AABB[$damagedName] as $info){
            $time = $info["Time"];
            if(($time - $estimatedHitTime) > 0 && ($time - $estimatedHitTime) < 5){
                $timeDiff = ($time - $estimatedHitTime);
                $correctAABB = $info["AABB"];
                $ray = Ray::from($damager);
                $distance = $correctAABB->collidesRay($ray, 0, 10);
                $maxDistance = ($damager->isCreative() ? 6 : 3) + ($timeDiff * 0.01);
                if($distance > $maxDistance){
                    $this->addPreVL($damagerName);
                    if($this->getPreVL($damagerName) >= 2){
                        $this->addViolation($damagerName);
                        $data = [
                            "VL" => self::getCurrentViolations($damagerName),
                            "Distance" => round($distance, 2)
                        ];
                        $this->notifyStaff($damagerName, $this->getName(), $data);
                    }
                } else {
                    $this->lowerPreVL($damagerName);
                }
                return;
            }
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if(!isset($this->AABB[$name])){
            $this->AABB[$name] = [];
        }
        if(count($this->AABB[$name]) === 40){
            array_shift($this->AABB[$name]);
        }
        $this->AABB[$name][] = ["Time" => microtime(true) * 1000, "AABB" => AABB::fromPosition($event->getTo())];
    }

}