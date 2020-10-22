<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class FlyB extends Detection{

    private $lastOnGround = true;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            // let ground spoof handle any bypasses for this
            $this->lastOnGround = $user->onGround;
        } elseif($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_JUMP){
            if(!$this->lastOnGround && !$user->onGround){
                if(++$this->preVL >= 2){
                    $this->fail($user);
                }
            } else {
                $this->preVL = 0;
                $this->reward($user, 0.995);
            }
        }
    }

}