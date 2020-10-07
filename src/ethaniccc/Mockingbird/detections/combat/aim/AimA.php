<?php

namespace ethaniccc\Mockingbird\detections\combat\aim;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class AimA extends Detection{

    private $equalnessSamples = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket && $user->isDesktop){
            if($user->timeSinceAttack <= 25 && $user->targetEntity !== null){
                if($user->yawDelta !== null && $user->lastYawDelta !== null){
                    if($user->yawDelta >= 20 || $user->lastYawDelta >= 20 || $user->yawDelta == 0 || $user->lastYawDelta == 0){
                        return;
                    }
                    $equalness = abs($user->yawDelta - $user->lastYawDelta);
                    if(count($this->equalnessSamples) === 20){
                        array_shift($this->equalnessSamples);
                    }
                    $this->equalnessSamples[] = $equalness;
                    // lol
                    if(count($this->equalnessSamples) === 20){
                        $deviation = MathUtils::getDeviation($this->equalnessSamples);
                        $average = MathUtils::getAverage($this->equalnessSamples);
                        if($equalness < 1 && $deviation < 0.85 && $average < 1){
                            if(++$this->preVL >= 10){
                                $this->fail($user, "eq: $equalness, avg: $average, dv: $deviation");
                                --$this->preVL;
                            }
                        } else {
                            $this->preVL -= $this->preVL > 0 ? 0.5 : 0;
                            if($this->preVL <= 1.25){
                                $this->reward($user, 0.999);
                            }
                        }
                    }
                }
            }
        }
    }

}