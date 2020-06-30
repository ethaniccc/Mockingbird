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

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class NoKnockback extends Cheat{

    private $cooldown = [];
    private $previousMotion = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onDamage(EntityDamageByEntityEvent $event){
        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player) return;
        $previousMotion = $damaged->getMotion();
        $name = $damaged->getName();

        $this->previousMotion[$name] = $damaged->getMotion();

        if(!isset($this->cooldown[$name])){
            $this->cooldown[$name] = $this->getServer()->getTick();
        } else {
            if($this->getServer()->getTick() - $this->cooldown[$name] >= 10){
                $this->cooldown[$name] = $this->getServer()->getTick();
            } else {
                return;
            }
        }

        $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($name, $damaged) : void{
            if($damaged->getMotion()->distance($this->previousMotion[$name]) == 0){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damaged));
            }
        }), 2);
    }

}