<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class VelocityB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 15;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceMotion <= ($user->transactionLatency / 50) + 4 && $user->moveData->lastMotion !== null && $user->player->isAlive()){
                if($user->timeSinceTeleport <= 6){
                    $this->preVL = 0;
                    return;
                }
                $forward = $packet->getMoveVecZ();
                $strafe = $packet->getMoveVecX();
                $motion = clone $user->moveData->lastMotion;
                // replication: https://github.com/eldariamc/client/blob/c01d23eb05ed83abb4fee00f9bf603b6bc3e2e27/src/main/java/net/minecraft/entity/EntityFlying.java#L30
                $f = pow($strafe, 2) + pow($forward, 2);
                if($f >= 9.999999747378752E-5){
                    $f = sqrt($f);
                    if($f < 1){
                        $f = 1;
                    }
                    $onGround = fmod(round($user->moveData->location->y, 4), 1/64) === 0.0;
                    $friction = $onGround ? 0.16277136 / pow($user->moveData->blockBelow->getFrictionFactor(), 3) : 0.02;
                    $f = $friction / $f;
                    $strafe *= $f;
                    $forward *= $f;
                    $f2 = sin($user->moveData->yaw * M_PI / 180);
                    $f3 = cos($user->moveData->yaw * M_PI / 180);
                    $motion->x += $strafe * $f3 - $forward * $f2;
                    $motion->z += $forward * $f3 + $strafe * $f2;
                }
                $motion->x *= 0.998;
                $motion->z *= 0.998;
                $expectedHorizontal = hypot($motion->x, $motion->z);
                // if the horizontal knockback is too low I don't want to deal with it
                if($expectedHorizontal < 0.1){
                    return;
                }
                $horizontalMove = hypot($user->moveData->moveDelta->x, $user->moveData->moveDelta->z);
                $percentage = $horizontalMove / $expectedHorizontal;
                $maxPercentage = $this->getSetting("multiplier");
                if($user->timeSinceAttack <= 2) {
                    $maxPercentage *= 0.98;
                }
                $blocksCollide = count($user->player->getLevel()->getCollisionBlocks($user->moveData->AABB->expand(0.2, 0, 0.2), true)) > 0;
                $scaledPercentage = ($horizontalMove / ($expectedHorizontal * $maxPercentage)) * 100;
                // $user->sendMessage("percentage=$scaledPercentage preVL={$this->preVL}");
                if($percentage < $maxPercentage && $user->moveData->cobwebTicks >= 6 && $user->moveData->liquidTicks >= 6 && $user->timeSinceStoppedFlight >= 20 && !$blocksCollide){
                    if(++$this->preVL > ($user->transactionLatency > 150 ? 40 : 30)){
                        $keyList = count($user->moveData->pressedKeys) > 0 ? implode(", ", $user->moveData->pressedKeys) : "none";
                        $this->fail($user, "percentage=$scaledPercentage keys=$keyList");
                    }
                } else {
                    $this->preVL = 0;
                    $this->reward($user, 0.995);
                }
            }
        }
    }

}