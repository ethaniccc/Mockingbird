<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;

class ToolboxKillaura extends Cheat{

    private $attackCooldown = [];
    private $allowedToHit = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof AnimatePacket){
            if($packet->action === AnimatePacket::ACTION_SWING_ARM){
                if(!isset($this->allowedToHit[$name])) $this->allowedToHit[$name] = true;
                $this->allowedToHit[$name] = true;
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
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
                $this->addViolation($name);
                $data = [
                    "VL" => $this->getCurrentViolations($name),
                    "Ping" => $damager->getPing()
                ];
                $this->notifyStaff($name, $this->getName(), $data);
            } else {
                unset($this->allowedToHit[$name]);
            }
        }
    }

}