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
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class ReachA extends Cheat{

    /** @var array */
    private $locationHistory, $distances = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->locationHistory[$name])){
            $this->locationHistory[$name] = [];
        }
        if(count($this->locationHistory[$name]) === 40){
            array_shift($this->locationHistory[$name]);
        }
        $this->locationHistory[$name][] = ["Time" => MathUtils::getTimeMS(), "Location" => $event->getTo()];
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

        if(isset($this->locationHistory[$damagedName])){
            if(count($this->locationHistory[$damagedName]) >= 20){
                $info = $this->getApproxHitClient(MathUtils::getTimeMS() - $damager->getPing(), $this->locationHistory[$damagedName]);
                if($info !== null){
                    $AABB = $info["AABB"];
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
                            $expectedDistance = $damager->getY() >= $damaged->getY() ? 3.15 : 4;
                            if($averageDistance > $expectedDistance && $distance > $expectedDistance){
                                $this->addPreVL($damagerName);
                                if($this->getPreVL($damagerName) >= 1.25){
                                    $this->suppress($event);
                                    $this->fail($damager, "$damagerName hit $damagedName at a distance of $averageDistance blocks.");
                                    $this->lowerPreVL($damagerName, 0.5);
                                }
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
     * @return array|null
     * This function's intent is to give the same functionality TreeMap#floorKey gives in Java.
     * TODO: There has to be a better way to do this right? (PR's open ;p)
     */
    private function getApproxHitClient(float $approximateTime, array $history) : ?array{
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
                return ["AABB" => AABB::fromPosition($info["Location"]), "Location" => $info["Location"]];
            }
        }
        return null;
    }

}