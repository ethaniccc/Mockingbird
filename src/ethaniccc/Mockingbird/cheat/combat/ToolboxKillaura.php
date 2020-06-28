<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;

class ToolboxKillaura extends Cheat{

    private $attackCooldown = [];
    private $allowedToHit = [];

    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof AnimatePacket){
            if($packet->action === AnimatePacket::ACTION_SWING_ARM){
                if(!isset($this->allowedToHit[$name])) $this->allowedToHit[$name] = microtime(true);
                $this->allowedToHit[$name] = microtime(true);
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        if($event instanceof EntityDamageByChildEntityEvent) return;
        if($damager instanceof Player){
            $name = $damager->getName();
            if(!isset($this->attackCooldown[$name])){
                $this->attackCooldown[$name] = microtime(true);
            } else {
                if(microtime(true) - $this->attackCooldown[$name] >= 0.5){
                    $this->attackCooldown[$name] = microtime(true);
                } else {
                    return;
                }
            }
            if(!isset($this->allowedToHit[$name])){
                if(!isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] = 0;
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] >= 2){
                    $this->addViolation($name);
                    $data = [
                        "VL" => $this->getCurrentViolations($name),
                        "Ping" => $damager->getPing()
                    ];
                    $this->notifyStaff($name, $this->getName(), $data);
                    $this->suspicionLevel[$name] = 0;
                }
            } else {
                $time = microtime(true) - $this->allowedToHit[$name];
                if($time >= 0.20){
                    if(!isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] = 0;
                    $this->suspicionLevel[$name] += 1;
                    if($this->suspicionLevel[$name] >= 2){
                        $this->addViolation($name);
                        $data = [
                            "VL" => $this->getCurrentViolations($name),
                            "Ping" => $damager->getPing()
                        ];
                        $this->notifyStaff($name, $this->getName(), $data);
                        $this->suspicionLevel[$name] = 0;
                    }
                } else {
                    if(!isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] = 0;
                    $this->suspicionLevel[$name] *= 0.5;
                }
                unset($this->allowedToHit[$name]);
            }
        }
    }

}