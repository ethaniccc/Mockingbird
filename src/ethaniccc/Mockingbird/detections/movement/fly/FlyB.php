<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class FlyB extends Detection implements CancellableMovement{

    private $lastOnGround = true;
    private $modulo = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $this->modulo = fmod(round($user->moveData->location->y, 4), 1 / 64);
            $this->lastOnGround = $this->modulo === 0.0;
        } elseif($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_JUMP){
            if(!$this->lastOnGround && $user->moveData->offGroundTicks >= 10 && !$user->player->isImmobile()){
                $this->fail($user, "modulo={$this->modulo} offGround={$user->moveData->offGroundTicks}");
            } else {
                $this->reward($user, 0.995);
            }
        }
    }

}