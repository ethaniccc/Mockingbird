<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class BadPacketB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 1;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket && ++$this->preVL >= 2){
            $this->fail($user);
        } elseif($packet instanceof PlayerAuthInputPacket){
            $this->reward($user, 0.999);
            $this->preVL *= 0.75;
        }
    }

}