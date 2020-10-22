<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\user\User;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class VelocityB extends Detection{

    private $queue = [];

    // nope nope nope nope nope nope - not yet
    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->enabled = false;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $forward = $packet->getMoveVecZ();
            $strafe = $packet->getMoveVecX();
            if($user->timeSinceTeleport < 2){
                $this->queue = [];
                return;
            }
            if(!empty($this->queue)){
                $data = $this->queue[0];
                $motion = clone $data->motion;
                if(++$data->time <= $data->maxTime){
                    // replicate moveFlying
                    $f = $strafe * $strafe + $forward * $forward;
                    if($f >= 9.999999747378752E-5){
                        $f = sqrt($f);
                        if($f < 1){
                            $f = 1;
                        }
                        $f = 0.98 / $f;
                        $strafe *= $f;
                        $forward *= $f;
                        $f2 = sin($packet->getYaw() * 3.141592653589793 / 180);
                        $f3 = cos($packet->getYaw() * 3.141592653589793 / 180);
                        $x = $strafe * $f3 - $forward * $f2;
                        $z = $forward * $f3 - $strafe * $f2;
                        if($user->moveDelta->x + $x <= $motion->x * 0.99 || $user->moveDelta->z + $z <= $motion->x * 0.99){
                            ++$data->failedTime;
                        }
                    }
                } else {
                    if($data->failedTime >= $data->maxTime){
                        if(++$this->preVL >= 3){
                            $this->debug("failed: {$data->motion}", false);
                        }
                    } else {
                        $this->preVL = 0;
                    }
                    $this->queue[0] = null;
                    array_shift($this->queue);
                    if(!empty($this->queue)){
                        $this->handle($packet, $user);
                    }
                }
            }
        } elseif($packet instanceof MotionPacket){
            if(count($this->queue) > 3){
                return;
            }
            $info = new \stdClass();
            $info->maxTime = (int) ($user->transactionLatency / 50) + 3;
            $info->motion = new Vector3($packet->motionX, $packet->motionY, $packet->motionZ);
            $info->time = 0;
            $info->failedTime = 0;
            $this->queue[] = $info;
        }
    }

}