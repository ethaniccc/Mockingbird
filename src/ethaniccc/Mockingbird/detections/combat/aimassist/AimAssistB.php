<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimAssistB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket && $user->win10 && $user->moveData->yawDelta > 0.0065 && $user->moveData->pitchDelta > 0.0065 && $user->moveData->yawDelta < 20 && $user->moveData->pitchDelta < 10){
        }
    }

}