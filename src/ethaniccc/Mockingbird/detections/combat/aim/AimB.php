<?php

namespace ethaniccc\Mockingbird\detections\combat\aim;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimB extends Detection{

    // 2 ^ 24
    private $expander = 16777216;
    private $lastDelta;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 5;
        $this->lowMax = 3;
        $this->mediumMax = 5;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($user->win10 && $packet instanceof PlayerAuthInputPacket){
            if($user->moveData->yawDelta < 15 && $user->moveData->pitchDelta < 5.5 && abs($user->moveData->pitchDelta) <= 85){
                $threshold = 26640;
                $yawGCD = $this->getGCD($user->moveData->yawDelta * $this->expander, $user->moveData->lastYawDelta * $this->expander);
                $pitchGCD = $this->getGCD($user->moveData->pitchDelta * $this->expander, $user->moveData->lastPitchDelta * $this->expander);
                if($yawGCD > 0 && $pitchGCD > 0){
                    $delta = abs($yawGCD - $pitchGCD);
                    if($yawGCD < $threshold || $pitchGCD < $threshold){
                        if($this->lastDelta !== null){
                            $deltaDiff = abs($delta - $this->lastDelta);
                            if($deltaDiff > 512 && $deltaDiff < 100000){
                                if(++$this->preVL >= 3){
                                    $this->preVL = min($this->preVL, 6);
                                    $this->fail($user, "deltaDiff=$deltaDiff");
                                }
                            } else {
                                $this->preVL -= $this->preVL > 0 ? 1 : 0;
                            }
                        }
                    } else {
                        $this->reward($user, 0.999);
                        $this->preVL -= $this->preVL > 0 ? 1 : 0;
                    }
                    $this->lastDelta = $delta;
                }
            }
        }
    }

    private function getGCD(float $a, float $b) : float{
        return $b <= 16384 ? $a : $this->getGCD($b, fmod($a, $b));
    }

}