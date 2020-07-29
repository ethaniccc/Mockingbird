<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\event\MoveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class AimA extends Cheat{

    /** @var array */
    private $movements = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damager instanceof Player || !$damaged instanceof Player){
            return;
        }
        $damagerName = $damager->getName();
        $damagedName = $damaged->getName();

        $currentYaw = $damager->getYaw();

        if(isset($this->movements[$damagedName])){
            $approximatePosition = null;
            $currentTime = microtime(true) * 1000;
            foreach($this->movements[$damagedName] as $arrayInfo){
                $time = $arrayInfo["Time"];
                if(abs($currentTime - $time) < 5){
                    if($approximatePosition === null){
                        $approximatePosition = ["Position" => $arrayInfo["Position"], "TDiff" => abs($currentTime - $time)];
                    } else {
                        if(abs($currentTime - $time) < $approximatePosition["TDiff"]){
                            $approximatePosition = ["Position" => $arrayInfo["Position"], "TDiff" => abs($currentTime - $time)];
                        }
                    }
                }
            }
            if($approximatePosition === null){
                $this->debug("No approximate position found for $damagerName hitting $damagedName");
            } else {
                $position = $approximatePosition["Position"];
                $perfectAim = MathUtils::getPerfectAim($damager, $position);
                $perfectYaw = $perfectAim["Yaw"];
                $yawDiff = round(abs($perfectYaw - $currentYaw), 5);
                if($yawDiff < 0.1){
                    $this->addPreVL($damagerName);
                    if($this->getPreVL($damagerName) >= 3){
                        $this->notify("$damagerName failed a check for AimA");
                        $this->lowerPreVL($damagerName, 0);
                    }
                } else {
                    $this->lowerPreVL($damagerName, 0.5);
                }
            }
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->movements[$name])){
            $this->movements[$name] = [];
        }
        if(count($this->movements[$name]) === 40){
            array_shift($this->movements[$name]);
        }
        array_push($this->movements[$name], ["Position" => $player->asVector3(), "Time" => microtime(true) * 1000]);
    }

}