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
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class Aimbot extends Cheat{

    private $hits = [];

    public function __construct(Plugin $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damager instanceof Player) return;
        $name = $damager->getName();
        $this->aimbotCheck($damager, $damaged);
    }

    private function aimbotCheck(Player $damager, Entity $target) : void{
        //This is basically copied from the function lookAt in the Living class.
        $horizontal = sqrt(($target->getX() - $damager->getX()) ** 2 + ($target->getZ() - $damager->getZ()) ** 2);
        $vertical = $target->getY() - $damager->getY();
        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

        if(!isset($this->hits[$damager->getName()])) $this->hits[$damager->getName()] = [];
        array_push($this->hits[$damager->getName()], round($pitch) - round($damager->getPitch()));
        $average = array_sum($this->hits[$damager->getName()]) / count($this->hits[$damager->getName()]);
        if(count($this->hits[$damager->getName()]) >= 30){
            if($average > 0 && $average < 2){
                $this->addViolation($damager->getName());
                $data = [
                    "VL" => $this->getCurrentViolations($damager->getName()),
                    "Ping" => $damager->getPing()
                ];
                $this->notifyStaff($damager->getName(), $this->getName(), $data);
            }
            unset($this->hits[$damager->getName()]);
            $this->hits[$damager->getName()] = [];
        }
    }
}