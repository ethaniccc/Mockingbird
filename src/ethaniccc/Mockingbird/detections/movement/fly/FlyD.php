<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class FlyD
 * @package ethaniccc\Mockingbird\detections\movement\fly
 * FlyD checks if the current Y movement of the player is greater than the last
 * Y movement of the player. The reason why this is invalid without some requirements being met
 * is because your Y movement should always be going down, as seen by FlyA [@see FlyA], which
 * replicates minecraft's calculation for Y movement to create a prediction.
 * P.S - Haha N1PE no bypass 4u >:)
 */
class FlyD extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 5;
        $this->lowMax = 3;
        $this->mediumMax = 4;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket && $user->loggedIn){
            if($user->moveData->offGroundTicks >= 10){
                $yDelta = $user->moveData->moveDelta->y;
                $lastYDelta = $user->moveData->lastMoveDelta->y;
                // this is invalid as your Y delta should always be going down (unless certain conditions)
                if($yDelta > $lastYDelta
                && $user->timeSinceMotion >= 5
                && $user->moveData->cobwebTicks >= 10
                && $user->moveData->liquidTicks >= 10
                && $user->timeSinceStoppedGlide >= 10
                && $user->timeSinceStoppedFlight >= 10
                && $user->timeSinceJoin >= 200
                && $user->timeSinceTeleport >= 10){
                    if(++$this->preVL >= 2){
                        $this->fail($user, "yDelta=$yDelta lastYDelta=$lastYDelta");
                    }
                } else {
                    $this->preVL = max($this->preVL - 0.5, 0);
                    $this->reward($user, 0.999);
                }
            }
        }
    }

}