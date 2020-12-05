<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimAssistB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket && $user->win10 && $user->moveData->pitchDelta > 0.0065 && abs($user->moveData->pitch) < 85){
            $gcd = MathUtils::getGCD($user->moveData->pitchDelta, $user->moveData->lastPitchDelta);
            $var = pow((5/6) * $gcd, 1/3);
            $common = (int) ((((5/3) * $var) - (1/3)) * 100);
            if($common < 0){
                if(++$this->preVL >= 30){
                    $this->preVL = min($this->preVL, 60);
                    $this->fail($user, "common=$common pitchDelta={$user->moveData->pitchDelta}");
                }
            } else {
                $this->preVL = max($this->preVL - 5, 0);
            }
            if($this->isDebug($user)){
                $user->sendMessage("common=$common gcd=$gcd");
            }
        }
    }

}