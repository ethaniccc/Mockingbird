<?php

namespace ethaniccc\Mockingbird\detections\combat\aim;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimB extends Detection{

    private $lastGCD = 0;
    private $expander;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        // just in case this still sometimes falses
        $this->lowMax = 15;
        $this->mediumMax = 20;
        $this->expander = pow(2, 24);
    }

    public function handle(DataPacket $packet, User $user): void{
        // make sure user is on desktop, player's pitchDelta is not too low, and player is not spamming their aim.
        if($user->isDesktop && $packet instanceof PlayerAuthInputPacket && $user->moveData->pitchDelta > 0.05 && $user->moveData->pitchDelta < 7.5 && abs(round($user->moveData->pitch, 0)) < 85 && abs(round($user->moveData->lastPitch, 0)) < 85){
            // this is the expander Elevated uses in his GCD checks
            $expander = $this->expander;
            $gcd = $this->getGCD($user->moveData->pitchDelta * $expander, $user->moveData->lastPitchDelta * $expander);
            $diff = abs($gcd - $this->lastGCD);
            // check if the GCD difference is within a range ----------------- why does this cause falses when player is near blocks
            if($diff >= 1000 && $diff < 100000 && count($user->player->getLevel()->getCollisionBlocks($user->moveData->AABB->expand(0.2, 0, 0.2), true)) === 0){
                // legit players can at some point false this, which is why the preVL
                // is so high - doesn't matter since this flags so much
                if(++$this->preVL >= 15){
                    $this->preVL = min($this->preVL, 20);
                    $this->fail($user, "pitchDelta={$user->moveData->pitchDelta} gcdDiff=$diff preVL={$this->preVL}");
                }
            } else {
                $this->reward($user, 0.999);
                $this->preVL -= $this->preVL > 0 ? ($this->preVL >= 2 ? 2 : 1) : 0;
            }
            $this->lastGCD = $gcd;
        }
    }

    // Elevated's GCD method
    private function getGCD(float $a, float $b) : float{
        return $b <= 16384 ? $a : $this->getGCD($b, fmod($a, $b));
    }

}