<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class BadPacketA
 * @package ethaniccc\Mockingbird\detections\packet\badpackets
 * BadPacketA checks if the user's pitch goes beyond +/- 90 (now 92 because of some weird bug).
 * This is common in weird things such as "Derp".
 */
class BadPacketA extends Detection implements CancellableMovement{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(abs($packet->getPitch()) > 92 && $user->timeSinceJoin >= 10){
                $this->fail($user, "{$user->player->getName()}: pitch={$packet->getPitch()}");
            }
        }
    }

}