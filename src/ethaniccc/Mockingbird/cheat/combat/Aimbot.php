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
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class Aimbot extends Cheat{

    private $hits = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
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

        $name = $damager->getName();
        if(!isset($this->hits[$name])) $this->hits[$name] = [];
        array_push($this->hits[$name], abs($pitch - $damager->getPitch()));
        $pitchAverage = array_sum($this->hits[$name]) / count($this->hits[$name]);
        $this->getServer()->broadcastMessage("$pitchAverage");
        if(count($this->hits[$name]) > 30) $this->hits[$name] = [];
    }
}