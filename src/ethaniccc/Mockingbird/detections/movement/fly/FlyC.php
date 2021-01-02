<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class FlyC
 * @package ethaniccc\Mockingbird\detections\movement\fly
 * FlyC checks if the current Y movement is too similar to the last Y movement. This falses when falling after
 * a while (hence "are PlayerAuthInputPacket y values fucked?"), which is why I check if the Y delta
 * is less greater -3, as at -3, this check loves to false.
 */
class FlyC extends Detection implements CancellableMovement{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->moveData->offGroundTicks >= 10){
                $yDelta = $user->moveData->moveDelta->y;
                $lastYDelta = $user->moveData->lastMoveDelta->y;
                $equalness = abs($yDelta - $lastYDelta);
                // are PlayerAuthInputPacket y values fucked?
                if($user->timeSinceJoin >= 200 && $yDelta > -3.0 && $equalness <= 0.01 && !$user->player->isFlying() && !$user->player->isSpectator() && $user->player->isAlive() && $user->timeSinceMotion > 5 && !$user->player->isImmobile() && $user->loggedIn
                    && $user->timeSinceStoppedFlight >= 10 && $user->moveData->blockBelow->getId() === 0 && $user->moveData->ticksSinceInVoid >= 10 && $user->moveData->cobwebTicks >= 15 && $user->moveData->liquidTicks >= 15 && $user->timeSinceStoppedGlide >= 10 && $user->moveData->levitationTicks >= 5){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "yD=$yDelta, eq=$equalness");
                    }
                } else {
                    $this->reward($user, 0.999);
                    $this->preVL *= 0.75;
                }
                if($this->isDebug($user)){
                    $user->sendMessage("yDiff=$equalness");
                }
            }
        }
    }

}