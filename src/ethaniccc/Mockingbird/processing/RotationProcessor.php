<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;

class RotationProcessor extends RunnableProcessor{

    public $mouseDeltaX;
    public $mouseDeltaY;

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function run() : void{
        $user = $this->user;
        if($user->moveData->yawDelta > 0.0065 && $user->moveData->lastYawDelta > 0.0065){
            $gcd = MathUtils::getGCD($user->moveData->yawDelta, $user->moveData->lastYawDelta);
            $this->mouseDeltaX = $user->moveData->yawDelta / $gcd;
        }
        if($user->moveData->pitchDelta > 0.0065 && $user->moveData->lastPitchDelta > 0.0065){
            $gcd = MathUtils::getGCD($user->moveData->pitchDelta, $user->moveData->lastPitchDelta);
            $this->mouseDeltaY = $user->moveData->pitchDelta / $gcd;
            // still working on figuring out how Minecraft bedrock does rotations :)
        }
    }

}