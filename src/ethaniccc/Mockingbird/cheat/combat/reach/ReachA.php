<?php

/*
$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\cheat\combat\reach;

use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $history, $distances, $onGroundTicks = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->history[$name])){
            $this->history[$name] = [];
        }
        if(count($this->history[$name]) === 40){
            array_shift($this->history[$name]);
        }
        $this->history[$name][] = ["Time" => MathUtils::getTimeMS(), "AABB" => AABB::fromPosition($event->getTo())];
        if(!isset($this->onGroundTicks[$name])){
            $this->onGroundTicks[$name] = 0;
        }
        if(LevelUtils::isNearGround($player)){
            ++$this->onGroundTicks[$name];
        } else {
            $this->onGroundTicks[$name] = 0;
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }

        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }

        if($damager->isCreative()){
            return;
        }

        $damagerName = $damager->getName();
        $damagedName = $damaged->getName();

        if(isset($this->history[$damagedName])){
            if(count($this->history[$damagedName]) >= 20){
                $AABB = $this->getApproxHitClient(MathUtils::getTimeMS() - $damager->getPing(), $this->history[$damagedName]);
                if($AABB !== null){
                    $ray = Ray::from($damager);
                    $distance = $AABB->collidesRay($ray, 0, 10);
                    if($distance != -1){
                        if(!isset($this->distances[$damagerName])){
                            $this->distances[$damagerName] = [];
                        }
                        if(count($this->distances[$damagerName]) === 20){
                            array_shift($this->distances[$damagerName]);
                        }
                        $this->distances[$damagerName][] = $distance;
                        if(count($this->distances[$damagerName]) === 20){
                            $averageDistance = MathUtils::getAverage($this->distances[$damagerName]);
                            $expectedDistance = $this->onGroundTicks[$damagedName] >= 10 ? 3.15 : 4;
                            if($averageDistance > $expectedDistance && $distance > $expectedDistance){
                                $this->addViolation($damagerName);
                                $this->notifyStaff($damagerName, $this->getName(), ["VL" => self::getCurrentViolations($damagerName), "Dist" => round($distance, 2)]);
                                $this->debugNotify("$damagerName hit $damagedName with a distance of $distance, while $expectedDistance expected. Average distance was higher than expected distance.");
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param float $approximateTime
     * @param array $history
     * @return AABB|null
     * This function's intent is to give the same functionality TreeMap#floorKey gives in Java.
     * TODO: There has to be a better way to do this right? (PR's open ;p)
     */
    private function getApproxHitClient(float $approximateTime, array $history) : ?AABB{
        $probables = [];
        $times = [];
        foreach($history as $info){
            $time = $info["Time"];
            if($time <= $approximateTime){
                $probables[] = $info;
                $times[] = $time;
            }
        }
        if(count($times) === 0){
            return null;
        }
        $approxTime = max($times);
        foreach($probables as $info){
            if($info["Time"] === $approxTime){
                return $info["AABB"];
            }
        }
        return null;
    }

}