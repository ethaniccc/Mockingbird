<?php

namespace ethaniccc\Mockingbird\detections\combat\aim;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimB extends Detection{

    private $lastGCD = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        $this->lowMax = 10;
        $this->mediumMax = 15;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket && $user->moveData->pitchDelta > 0.05 && abs(round($user->moveData->pitch, 0)) < 85 && abs(round($user->moveData->lastPitch, 0)) < 85){
            // this is the expander Elevated uses in his GCD checks
            $expander = pow(2, 24);
            $gcd = $this->getGCD($user->moveData->pitchDelta * $expander, $user->moveData->lastPitchDelta * $expander);
            $diff = abs($gcd - $this->lastGCD);
            // check if the GCD difference is within a range
            if($diff >= 1000 && $diff < 100000){
                // legit players can at some point false this, which is why the preVL
                // is so high - doesn't matter since this flags so much
                if(++$this->preVL >= 15){
                    $this->preVL = min($this->preVL, 20);
                    $this->fail($user, "gcdDiff=$diff preVL={$this->preVL}");
                }
            } else {
                $this->reward($user, 0.999);
                $this->preVL -= $this->preVL > 0 ? 1 : 0;
            }
            $this->lastGCD = $gcd;
        }
    }

    // Elevated's GCD method
    private function getGCD(float $a, float $b) : float{
        return $b <= 16384 ? $a : $this->getGCD($b, fmod($a, $b));
    }

}