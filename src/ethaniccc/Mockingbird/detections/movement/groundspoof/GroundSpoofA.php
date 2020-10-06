<?php

namespace ethaniccc\Mockingbird\detections\movement\groundspoof;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class GroundSpoofA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            $clientOnGround = $packet->onGround;
            $serverOnGround = $user->serverOnGround;
            if($clientOnGround && !$serverOnGround && $user->loggedIn){
                if(++$this->preVL >= 3){
                    $this->fail($user);
                }
            } else {
                $this->preVL *= 0.1;
            }
        }
    }
}