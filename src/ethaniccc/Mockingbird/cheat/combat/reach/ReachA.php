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

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\level\particle\DustParticle;

class ReachA extends Cheat{

    private $cooldown, $hitInfo = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    /**
     * @param PlayerHitPlayerEvent $event
     * @priority LOWEST
     */
    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getPlayerHit();
        if($damager->isCreative() || $damager->isSpectator()){
            return;
        }
        $damagerUser = $this->getPlugin()->getUserManager()->get($damager);
        $damagedUser = $this->getPlugin()->getUserManager()->get($damaged);
        if($damagerUser->getClientData()->isMobile()){
            // According to John (@John.#0658), tap to touch mobile players get more reach
            return;
        }
        $name = $damager->getName();
        $currentTick = $this->getServer()->getTick();
        if(!isset($this->cooldown[$name])){
            $this->cooldown[$name] = $currentTick;
        } else {
            if($currentTick - $this->cooldown[$name] >= $event->getAttackCoolDown()){
                $this->cooldown[$name] = $currentTick;
            } else {
                return;
            }
        }
        $estimatedLocation = $damagedUser->getLocationHistory()->getLocationRelativeToTime(MathUtils::getTimeMS() - $damager->getPing() - 55);
        if($estimatedLocation === null){
            return;
        }
        if($damagerUser->getAttackPosition() === null){
            return;
        }
        $AABB = AABB::fromPosition($estimatedLocation->subtract(0, 1.5, 0));
        $eyePos = $damagerUser->getAttackPosition()->subtract(0, 1.62, 0)->add(0, $damager->getEyeHeight(), 0);
        $distances = [];
        foreach($AABB->getCornerVectors() as $cornerVector){
            $distances[] = $cornerVector->distance($eyePos);
        }
        $distance = min($distances);
        $this->debugNotify("$distance");
        if($distance > $this->getSetting("max_reach")){
            $this->addPreVL($name);
            if($this->getPreVL($name) >= 1.5){
                $data = $this->basicFailData($damager);
                $data["{distance}"] = round($distance, 2);
                $this->fail($damager, $event, $this->formatFailMessage($data), [], "$name: d: $distance, mD: {$this->getSetting("max_reach")}");
            }
        } else {
            $this->lowerPreVL($name, 0.25);
        }

    }

}