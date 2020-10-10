<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class FlyC extends Detection{

    private $lastOnGround = true;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            // let ground spoof handle any bypasses for this
            $this->lastOnGround = $packet->onGround;
        } elseif($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_JUMP){
            if(!$this->lastOnGround){
                $this->fail($user);
            } else {
                $this->reward($user, 0.995);
            }
        }
    }

}