<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class VelocityB extends Detection{

    private $queue = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 15;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $forward = $packet->getMoveVecZ();
            $strafe = $packet->getMoveVecX();
            $keys = [];
            if($forward > 0){
                $keys[] = "W";
            } elseif($forward < 0){
                $keys[] = "S";
            }
            if($strafe > 0){
                $keys[] = "A";
            } elseif($strafe < 0){
                $keys[] = "D";
            }
            if($user->timeSinceTeleport < 2 || !$user->player->isAlive()){
                $this->queue = [];
                return;
            }
            if(!empty($this->queue)){
                $data = $this->queue[0];
                $motion = clone $data->motion;
                if(++$data->time <= $data->maxTime){
                    // replicate moveEntityWithHeading: https://github.com/eldariamc/client/blob/c01d23eb05ed83abb4fee00f9bf603b6bc3e2e27/src/main/java/net/minecraft/entity/EntityFlying.java#L30
                    $f = $strafe * $strafe + $forward * $forward;
                    if($f >= 9.999999747378752E-5){
                        $f = sqrt($f);
                        if($f < 1){
                            $f = 1;
                        }
                        $f = 0.02 / $f;
                        $strafe *= $f;
                        $forward *= $f;
                        $f2 = sin($packet->getYaw() * 3.141592653589793 / 180);
                        $f3 = cos($packet->getYaw() * 3.141592653589793 / 180);
                        $motion->x += $strafe * $f3 - $forward * $f2;
                        $motion->z += $forward * $f3 - $strafe * $f2;
                    }
                    $motion->x *= 0.98;
                    $motion->z *= 0.98;
                    // now it's time to check percentage, at least one time the percentage should be
                    // near 100 - by default percentage is 95 to prevent too many false positives
                    // this detects at max 90% horizontal velocity (poggers!) anything below
                    // or at 90% should be constantly detected
                    $expectedHorizontal = hypot($motion->x, $motion->z);
                    if($expectedHorizontal < 0.1){
                        return;
                    }
                    $notSolidBlocksAround = count($user->player->getBlocksAround());
                    $solidBlocksAround = count($user->player->getLevel()->getCollisionBlocks(AABB::from($user)->expand(0.2, 0, 0.2)));
                    $horizontalMove = hypot($user->moveDelta->x, $user->moveDelta->z);
                    $percentage = $horizontalMove/ $expectedHorizontal;
                    if($percentage < $this->getSetting("multiplier") && $notSolidBlocksAround === 0 && $solidBlocksAround === 0){
                        ++$data->failedTime;
                    }
                    if($percentage * 100 > $data->maxPercentage){
                        $data->maxPercentage = $percentage * 100;
                    }
                } else {
                    if($data->failedTime >= $data->maxTime){
                        if(++$this->preVL >= 6){
                            // fucking GG horizontal velocity users
                            $keyList = count($keys) > 0 ? implode(",", $keys) : "none";
                            $this->fail($user, "maxPercentage={$data->maxPercentage} keys=$keyList");
                            --$this->preVL;
                        }
                    } else {
                        $this->preVL -= $this->preVL > 0 ? 1 : 0;
                        if($this->preVL <= 2){
                            $this->reward($user, 0.99);
                        }
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
            $info->maxPercentage = 0;
            $this->queue[] = $info;
        }
    }

}