<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class VelocityB
 * @package ethaniccc\Mockingbird\detections\movement\velocity
 * VelocityB checks if the user's horizontal velocity is lower than normal.
 * This check is still experimental and I'm working on improving it.
 * THIS CHECK IS UNSTABLE
 * TODO: Figure out what other PvP clients are doing with their Velocity modifiers to bypass this check at higher settings.
 */
class VelocityB extends Detection{

    private $previousKeys;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 15;
        $this->lowMax = 10;
        $this->mediumMax = 15;
        // TODO: Fix this check - falses in some scenarios.
    }

    public function handleReceive(DataPacket $packet, User $user): void{
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
                if($f >= 1E-4){
                    $f = sqrt($f);
                    if($f < 1){
                        $f = 1;
                    }
                    $onGround = fmod(round($user->moveData->location->y, 4), 0.015625) === 0.0;
                    $friction = $onGround ? ((0.16277136 / pow($user->moveData->blockBelow->getFrictionFactor(), 3)) * 0.1) : 0.02;
                    $f = $friction / $f;
                    $strafe *= $f;
                    $forward *= $f;
                    $f2 = sin($user->moveData->yaw * 3.141592653589793 / 180);
                    $f3 = cos($user->moveData->yaw * 3.141592653589793 / 180);
                    $motion->x += $strafe * $f3 - $forward * $f2;
                    $motion->z += $forward * $f3 + $strafe * $f2;
                }
                $motion->x *= 0.998;
                $motion->z *= 0.998;
                $expectedHorizontal = hypot($motion->x, $motion->z);
                // if the horizontal knockback is too low I don't want to deal with it
                if($expectedHorizontal < 0.2){
                    return;
                }
                $horizontalMove = hypot($user->moveData->moveDelta->x, $user->moveData->moveDelta->z);
                $percentage = $horizontalMove / $expectedHorizontal;
                $maxPercentage = $this->getSetting('multiplier');
                // check if any blocks collide with the user's expanded AABB to prevent falses.
                $blocksCollide = count($user->player->getLevel()->getCollisionBlocks($user->moveData->AABB->clone()->expand(0.2, 0, 0.2), true)) > 0;
                $scaledPercentage = ($horizontalMove / $expectedHorizontal) * 100;
                $keyList = count($user->moveData->pressedKeys) > 0 ? implode(", ", $user->moveData->pressedKeys) : "none";
                $hasSameKeys = $keyList === $this->previousKeys;
                if($percentage < $maxPercentage && $user->moveData->cobwebTicks >= 6 && $user->moveData->liquidTicks >= 6 && $user->timeSinceStoppedFlight >= 20 && !$blocksCollide && $hasSameKeys){
                    if(++$this->preVL > ($user->transactionLatency > 150 ? 40 : 30)){
                        $this->fail($user, "percentage(horizontal)=$scaledPercentage% keys=$keyList buffer={$this->preVL}");
                        $this->preVL = min($this->preVL, 50);
                    }
                } else {
                    $hasSameKeys ? $this->preVL = max($this->preVL - 15, 0) : $this->preVL = max($this->preVL - 1.5, 0);
                    $this->reward($user, 0.995);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("percentage=$scaledPercentage% buffer={$this->preVL}");
                }
                $this->previousKeys = $keyList;
            }
        }
    }

}