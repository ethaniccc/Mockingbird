<?php

namespace ethaniccc\Mockingbird\cheat\movement\velocity;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\Vector4;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class VelocityA extends Cheat{

    private $locationHistory, $expectedMotion, $motion, $ticksSinceSend, $cooldown = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        $to = $event->getTo();
        $from = $event->getFrom();

        $dx = $to->x - $from->x;
        $dy = $to->y - $from->y;
        $dz = $to->z - $from->z;

        $currentMotion = new Vector3($dx, $dy, $dz);

        if(!isset($this->locationHistory[$name])){
            $this->locationHistory[$name] = [];
        }
        if(count($this->locationHistory[$name]) === 40){
            array_shift($this->locationHistory[$name]);
        }

        $attacked = isset($this->ticksSinceSend[$name]);
        if($attacked){
            $maxTicks = ($player->getPing() / 50) + 5;
            ++$this->ticksSinceSend[$name];
            if($this->ticksSinceSend[$name] <= $maxTicks){
                $expectedMotion = $this->expectedMotion[$name] ?? null;
                if($expectedMotion instanceof Vector3){
                    $deltaX = $expectedMotion->x - $currentMotion->x;
                    $deltaZ = $expectedMotion->z - $currentMotion->z;
                    if((abs($deltaX) > 0.075 && $deltaX < 0) && (abs($deltaZ) > 0.075 && $deltaZ < 0) && MathUtils::getTimeMS() - end($this->locationHistory[$name])->time <= 500){
                        $this->addPreVL($name);
                        $this->debugNotify("$deltaX && $deltaZ - expected ({$expectedMotion->x} && {$expectedMotion->z})");
                    }
                    $this->expectedMotion[$name] = $expectedMotion->multiply(1.98);
                }
            } else {
                if($this->getPreVL($name) >= floor($maxTicks)){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
                $this->lowerPreVL($name, 0);
                unset($this->ticksSinceSend[$name]);
                unset($this->expectedMotion[$name]);
            }
        }

        $this->locationHistory[$name][] = new Vector4($to->x, $to->y, $to->z, MathUtils::getTimeMS());
        $this->motion[$name] = $currentMotion;
    }

    public function onHit(PlayerHitPlayerEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getPlayerHit();

        if(isset($this->cooldown[$damaged->getName()])){
            if($this->getServer()->getTick() - $this->cooldown[$damaged->getName()] >= $event->getAttackCooldown()){
                $this->cooldown[$damaged->getName()] = $this->getServer()->getTick();
            } else {
                return;
            }
        } else {
            $this->cooldown[$damaged->getName()] = $this->getServer()->getTick();
        }

        $predictedDamagerHitTime = MathUtils::getTimeMS() - $damager->getPing();
        $predictedDamagedLocation = $this->predictClientSideLocation($this->locationHistory[$damaged->getName()], $predictedDamagerHitTime);
        $predictedDamagerLocation = $this->predictClientSideLocation($this->locationHistory[$damager->getName()], $predictedDamagerHitTime);
        if($predictedDamagedLocation !== null && $predictedDamagerLocation !== null){
            // reference: https://github.com/eldariamc/client/blob/master/src/main/java/net/minecraft/entity/EntityLivingBase.java#L1041-L1060
            $dx = $predictedDamagedLocation->x - $predictedDamagerLocation->x;
            $dz = $predictedDamagedLocation->z - $predictedDamagerLocation->z;
            $var7 = sqrt($dx * $dx + $dz * $dz);
            if($var7 == 0){
                $this->expectedMotion[$damaged->getName()] = new Vector3(0, 0, 0);
                return;
            }
            $knockback = $event->getKnockBack();
            $expectedMotion = $this->getPlayerMotion($damaged);
            $expectedMotion->x /= 2;
            $expectedMotion->y /= 2;
            $expectedMotion->z /= 2;
            $expectedMotion->x -= $dx / $var7 * $knockback;
            $expectedMotion->y += $knockback;
            $expectedMotion->z -= $dx / $var7 * $knockback;
            if($expectedMotion->y > 0.4000000059604645){
                $expectedMotion->y = 0.4000000059604645;
            }
            $angle = atan2($dx, $dz);
            $this->expectedMotion[$damaged->getName()] = $expectedMotion;
            $this->ticksSinceSend[$damaged->getName()] = 0;
            $this->debugNotify("$angle");
        }

    }

    private function getPlayerMotion(Player $player) : Vector3{
        return $this->motion[$player->getName()] ?? new Vector3(0, 0, 0);
    }

    private function predictClientSideLocation(array $locationHistory, float $estimatedTime) : ?Vector4{
        $probableLocation = null;
        foreach($locationHistory as $location){
            if($probableLocation === null){
                if($location->time <= $estimatedTime){
                    $probableLocation = $location;
                }
            } else {
                if($location->time <= $estimatedTime && $location->time > $probableLocation->time){
                    $probableLocation = $location;
                }
            }
        }
        return $probableLocation;
    }

}