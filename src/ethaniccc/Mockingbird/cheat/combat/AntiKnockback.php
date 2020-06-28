<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\entity\Attribute;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class AntiKnockback extends Cheat{

    private $cooldown = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, false);
        $this->getServer()->getLogger()->debug("AntiKnockback is an inaccurate check and has been disabled.");
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $name = $entity->getName();
            if(!isset($this->cooldown[$name])){
                $this->cooldown[$name] = $this->getServer()->getTick();
            } else {
                if($this->getServer()->getTick() - $this->cooldown[$name] >= 10){
                    $this->cooldown[$name] = $this->getServer()->getTick();
                } else {
                    return;
                }
            }
            $previousMotion = $entity->getMotion();
            $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($previousMotion, $entity, $name) : void{
                if($entity->getMotion() == $previousMotion){
                    $this->addViolation($name);
                    $data = [
                        "VL" => $this->getCurrentViolations($name),
                        "Ping" => $entity->getPing()
                    ];
                    $this->notifyStaff($name, $this->getName(), $data);
                }
            }), 1);
        }
    }

}