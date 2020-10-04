<?php

namespace ethaniccc\Mockingbird\detections\movement\nofall;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class NoFallA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function process(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            $clientOnGround = $packet->onGround;
            $serverOnGround = $user->serverOnGround;
            if($user->moveDelta !== null){
                if($user->offGroundTicks >= 3
                && $clientOnGround
                && !$serverOnGround
                && $user->moveDelta->y < 0){
                    if(++$this->preVL >= 3){
                        $this->fail($user);
                    }
                } else {
                    $this->preVL = 0;
                    if(!$serverOnGround){
                        $this->reward($user, 0.995);
                    }
                }
            }
        }
    }

}