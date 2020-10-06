<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class FlyB extends Detection implements MovementDetection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            if($user->offGroundTicks > 1){
                if($user->moveDelta === null || $user->lastMoveDelta === null){
                    return;
                }
                $yDelta = $user->moveDelta->y;
                $lastYDelta = $user->lastMoveDelta->y;
                if(($equalness = abs($yDelta - $lastYDelta)) <= 0.01 && $packet->mode === MovePlayerPacket::MODE_NORMAL && $user->timeSinceMotion >= 5){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "{$user->player->getName()}: yD: $yDelta, lYD: $lastYDelta, eq: $equalness");
                    }
                } else {
                    if(!$user->serverOnGround){
                        $this->preVL *= 0.5;
                        $this->reward($user, 0.995);
                    }
                }
            }
        }
    }

}