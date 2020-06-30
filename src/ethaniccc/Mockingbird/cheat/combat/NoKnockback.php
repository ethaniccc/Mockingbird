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
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Zombie;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class NoKnockback extends Cheat{

    /* @WARNING: This cheat might be super resource consuming since
    it will spawn a decoy every time a player is hit. I have not yet
    found a way to make this check less resource-consuming, so I am
    using this check for now. */

    private $cooldown = [];
    private $previousPosition = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, false);
    }

    public function onDamage(EntityDamageByEntityEvent $event){
        $damager = $event->getDamager();
        $damaged = $event->getEntity();

        if(!$damager instanceof Player || !$damaged instanceof Player) return;
        $previousMotion = $damaged->getMotion();
        $name = $damaged->getName();

        if(!isset($this->previousPosition[$name])){
            $this->previousPosition[$name] = $damaged->asVector3();
            return;
        }

        if(!isset($this->cooldown[$name])){
            $this->cooldown[$name] = $this->getServer()->getTick();
        } else {
            if($this->getServer()->getTick() - $this->cooldown[$name] >= 10){
                $this->cooldown[$name] = $this->getServer()->getTick();
            } else {
                return;
            }
        }

        $nbt = Entity::createBaseNBT($damaged->asVector3());
        $decoy = new Zombie($damaged->getLevel(), $nbt);
        $decoy->setCanSaveWithChunk(false);
        $decoy->setInvisible();
        $decoy->setMotion($damaged->getMotion());

        // Refer to the living class.

        $x = $damaged->getX() - $damager->getX();
        $z = $damaged->getZ() - $damager->getZ();

        $f = sqrt($x * $x + $z * $z);
        if($f <= 0){
            return;
        }

        if(mt_rand() / mt_getrandmax() > $damaged->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
            $f = 1 / $f;

            $expectedMotion = clone $damaged->getMotion();

            $expectedMotion->x /= 2;
            $expectedMotion->y /= 2;
            $expectedMotion->z /= 2;
            $expectedMotion->x += $x * $f * $event->getKnockBack();
            $expectedMotion->y += $event->getKnockBack();
            $expectedMotion->z += $z * $f * $event->getKnockBack();

            if($expectedMotion->y > $event->getKnockBack()){
                $expectedMotion->y = $event->getKnockBack();
            }

            $decoy->setMotion($expectedMotion);
        }

        $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($name, $damaged, $decoy) : void{
            $distX = $damaged->getX() - $decoy->getX();
            $distZ = $damaged->getZ() - $decoy->getZ();
            $distanceSquared = abs(($distX * $distX) + ($distZ * $distZ));
            $distance = sqrt($distanceSquared);

            if($distance > 0.4){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damaged));
            } elseif($damaged->asVector3()->distance($this->previousPosition[$name]) == 0){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($damaged));
            }

            $decoy->flagForDespawn();
            $decoy->close();
        }), 2);

        $this->previousPosition[$name] = $damaged->asVector3();
    }

}