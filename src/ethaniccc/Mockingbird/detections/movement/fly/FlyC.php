<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

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
                if($yDelta > -3.0 && $equalness <= 0.01 && !$user->player->isFlying() && $user->player->isAlive() && $user->timeSinceMotion > 5 && !$user->player->isImmobile() && $user->loggedIn && $user->timeSinceStoppedFlight >= 10){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "yD=$yDelta, eq=$equalness");
                    }
                } else {
                    $this->reward($user, 0.999);
                    $this->preVL *= 0.75;
                }
            }
        }
    }

}