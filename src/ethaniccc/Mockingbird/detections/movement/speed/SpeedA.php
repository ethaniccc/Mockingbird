<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class SpeedA extends Detection implements MovementDetection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function process(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            if($user->offGroundTicks > 1){
                $lastMoveDelta = $user->lastMoveDelta;
                $currentMoveDelta = $user->moveDelta;
                if($lastMoveDelta === null){
                    return;
                }
                $lastXZ = hypot($lastMoveDelta->x, $lastMoveDelta->z);
                $currentXZ = hypot($currentMoveDelta->x, $currentMoveDelta->z);
                $expectedXZ = $lastXZ * 0.91 + 0.026;
                $equalness = $currentXZ - $expectedXZ;
                if($equalness > $this->getSetting("max_breach")
                && $user->timeSinceMotion >= 3
                && !$user->player->isFlying()){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "{$user->player->getName()}: e: $equalness, cXZ: $currentXZ, lXZ: $lastXZ");
                    }
                } else {
                    if(!$user->serverOnGround){
                        $this->preVL = 0;
                        $this->reward($user, 0.999);
                    }
                }
            }
        }
    }

}