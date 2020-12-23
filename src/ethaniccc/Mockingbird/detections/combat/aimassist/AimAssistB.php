<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class AimAssistB
 * @package ethaniccc\Mockingbird\detections\combat\aimassist
 * This is Elevated's approach on how to detect some aimbot/aimassist
 *
 */
class AimAssistB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket && $user->win10 && $user->moveData->yawDelta > 0.0065 && $user->moveData->pitchDelta > 0.0065 && $user->moveData->yawDelta < 20 && $user->moveData->pitchDelta < 15){
            $expander = 16777216; // 2 ^ 24
            $yawConstant = ($this->getGCD($user->moveData->yawDelta * $expander, $user->moveData->lastYawDelta * $expander)) / $expander;
            $pitchConstant = ($this->getGCD($user->moveData->pitchDelta * $expander, $user->moveData->lastPitchDelta * $expander)) / $expander;
            if($yawConstant > 0 && $pitchConstant > 0){
                $currentXDelta = (int) ($user->moveData->yawDelta / $yawConstant);
                $currentYDelta = (int) ($user->moveData->pitchDelta / $pitchConstant);
                $previousXDelta = (int) ($user->moveData->lastYawDelta / $yawConstant);
                $previousYDelta = (int) ($user->moveData->lastPitchDelta / $pitchConstant);
                $moduloX = fmod($currentXDelta, $previousXDelta);
                $moduloY = fmod($currentYDelta, $previousYDelta);
                if($moduloX > 90 && $moduloY > 90 && $moduloX !== NAN && $moduloY !== NAN){
                    if(++$this->preVL >= 6){
                        $this->fail($user, "modX=$moduloX modY=$moduloY buffer={$this->preVL}");
                    }
                } else {
                    $this->preVL = 0;
                }
            }
        }
    }

    private function getGCD(float $a, float $b) : float{
        return $b <= 16384 ? $a : $this->getGCD($b, fmod($a, $b));
    }

}