<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimAssistB extends Detection{

    // 2 ^ 24
    private $expander = 16777216;
    private $lastYawGCD, $lastPitchGCD;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        $this->lowMax = 3;
        $this->mediumMax = 5;
    }

    public function handle(DataPacket $packet, User $user): void{
        // this check falses on mobile with very low sensitivity
        if($user->win10 && $packet instanceof PlayerAuthInputPacket){
            if($user->moveData->yawDelta < 15 && $user->moveData->pitchDelta < 5.5 && abs($user->moveData->pitchDelta) <= 85){
                $yawGCD = $this->getGCD($user->moveData->yawDelta * $this->expander, $user->moveData->lastYawDelta * $this->expander);
                $pitchGCD = $this->getGCD($user->moveData->pitchDelta * $this->expander, $user->moveData->lastPitchDelta * $this->expander);
                if($this->lastYawGCD !== null && $this->lastPitchGCD !== null){
                    if($yawGCD > 0 && $pitchGCD > 0){
                        $yawDiff = abs($yawGCD - $this->lastYawGCD);
                        $pitchDiff = abs($pitchGCD - $this->lastPitchGCD);
                        if($yawDiff > 1000 && $pitchDiff > 1000 && $yawDiff < 100000 && $pitchDiff < 100000){
                            if(++$this->preVL >= 10){
                                $this->preVL = min($this->preVL, 15);
                                $this->fail($user, "yawDiff=$yawDiff pitchDiff=$pitchDiff");
                            }
                        } else {
                            $this->reward($user, 0.999);
                            $this->preVL -= $this->preVL > 0 ? 1 : 0;
                        }
                        $this->debug("yawDiff=$yawDiff pitchDiff=$pitchDiff", false);
                    }
                }
                $this->lastYawGCD = $yawGCD;
                $this->lastPitchGCD = $pitchGCD;
            }
        }
    }

    private function getGCD(float $a, float $b) : float{
        return $b <= 16384 ? $a : $this->getGCD($b, fmod($a, $b));
    }

}