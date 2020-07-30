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

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;

class ToolboxKillaura extends Cheat implements StrictRequirements{

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
        if($damager instanceof Player){
            $name = $damager->getName();
            if(!isset($this->attackCooldown[$name])){
                $this->attackCooldown[$name] = $this->getServer()->getTick();
            } else {
                if($this->getServer()->getTick() - $this->attackCooldown[$name] >= 10){
                    $this->attackCooldown[$name] = $this->getServer()->getTick();
                } else {
                    return;
                }
            }
            if(!isset($this->allowedToHit[$name])){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 2){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damager));
                    $this->lowerPreVL($name, 0);
                }
            } else {
                $time = microtime(true) - $this->allowedToHit[$name];
                if($time >= 0.20){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 2){
                        $this->addViolation($name);
                        $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damager));
                        $this->lowerPreVL($name, 0);
                    }
                } else {
                    $this->lowerPreVL($name, 0.5);
                }
                unset($this->allowedToHit[$name]);
            }
        }
    }

}