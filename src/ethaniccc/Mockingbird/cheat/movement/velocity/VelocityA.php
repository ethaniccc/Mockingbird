<?php

namespace ethaniccc\Mockingbird\cheat\movement\velocity;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\block\BlockIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class VelocityA extends Cheat{

    /** @var array */
    private $attacked, $lastPos, $expectedMotion, $motion, $cooldown = [];

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

        $attacked = $this->attacked[$name] ?? false;
        if($attacked){
            if(($this->getServer()->getTick()) - $attacked <= 2){
                $expectedMotion = $this->expectedMotion[$name];
                // the is near ground check is to make sure the player's movement is not being interrupted by a block.
                $xDist = $expectedMotion->x - $currentMotion->x;
                $zDist = $expectedMotion->z - $currentMotion->z;
                if((abs($expectedMotion->x - $currentMotion->x) >= 0.05 && $xDist < 0) || (abs($expectedMotion->z - $currentMotion->z) >= 0.05 && $zDist < 0)){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->debugNotify("$name failed velocity check: DX: $dx DZ: $dy. Expected DX: {$expectedMotion->x} DZ: {$expectedMotion->z}");
                }

                unset($this->attacked[$name]);
                unset($this->lastPos[$name]);
                unset($this->expectedMotion[$name]);
            }
        }

        $this->motion[$name] = $currentMotion;
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damaged = $event->getEntity();
        $damager = $event->getDamager();
        if($damaged instanceof Player){

            if(isset($this->cooldown[$damaged->getName()])){
                if($this->getServer()->getTick() - $this->cooldown[$damaged->getName()] >= $event->getAttackCooldown()){
                    $this->cooldown[$damaged->getName()] = $this->getServer()->getTick();
                } else {
                    return;
                }
            } else {
                $this->cooldown[$damaged->getName()] = $this->getServer()->getTick();
            }

            $this->attacked[$damaged->getName()] = $this->getServer()->getTick();
            $this->lastPos[$damaged->getName()] = $damaged->asVector3();

            $dx = $damager->x - $damaged->x;
            $dy = $damager->y - $damaged->y;
            $dz = $damager->z - $damaged->z;

            // hello minecraft source uh copy pasta: https://github.com/eldariamc/client/blob/master/src/main/java/net/minecraft/entity/EntityLivingBase.java#L1041-L1060
            $var7 = sqrt($dx * $dx + $dz * $dz);
            if($var7 == 0){
                $this->expectedMotion[$damaged->getName()] = new Vector3(0, 0, 0);
                return;
            }
            $knockback = $event->getKnockBack();

            $expectedMotion = $this->getPlayerMotion($damaged->getName());
            $expectedMotion->x /= 2;
            $expectedMotion->y /= 2;
            $expectedMotion->z /= 2;

            $expectedMotion->x -= $dx / $var7 * $knockback;
            $expectedMotion->y += $knockback;
            $expectedMotion->z -= $dz / $var7 * $knockback;

            if($expectedMotion->y > 0.4000000059604645){
                $expectedMotion->y = 0.4000000059604645;
            }
            $this->expectedMotion[$damaged->getName()] = $expectedMotion;
        }
    }

    private function getPlayerMotion(string $name) : Vector3{
        return $this->motion[$name] ?? new Vector3(0, 0, 0);
    }

}