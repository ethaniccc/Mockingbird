<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class BadPacketA extends Detection implements MovementDetection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(abs($packet->getPitch()) > 90 && $user->timeSinceJoin >= 10){
                $this->fail($user, "{$user->player->getName()}: pitch={$packet->getPitch()}");
            }
        }
    }

}