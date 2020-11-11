<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class VelocityA extends Detection implements CancellableMovement{

    private $queue = [];
    private $blockCollidesTicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->suppression = false;
        $this->vlThreshold = 20;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof MotionPacket && $user->loggedIn){
            if(count($this->queue) > 5){
                return;
            }
            $info = new \stdClass();
            $info->motion = $packet->motionY;
            $info->maxTime = (int) ($user->transactionLatency / 50) + 3;
            $info->time = 0;
            $info->failedTime = 0;
            $info->maxFailedMotion = 0;
            $this->queue[] = $info;
        } elseif($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceTeleport < 2){
                $this->queue = [];
                return;
            }
            if(!empty($this->queue)){
                $currentData = $this->queue[0];
                if(++$currentData->time <= $currentData->maxTime){
                    $notSolidBlocksAround = count($user->player->getBlocksAround());
                    $AABB = AABB::from($user);
                    $AABB->maxY += 0.1;
                    if($notSolidBlocksAround > 0 || $user->moveData->blockAbove !== null){
                        $this->blockCollidesTicks = 0;
                    } else {
                        ++$this->blockCollidesTicks;
                    }
                    if($user->moveData->moveDelta->y < $currentData->motion * $this->getSetting("multiplier")
                    && $this->blockCollidesTicks >= 5 && $currentData->motion >= 0.3){
                        ++$currentData->failedTime;
                        if(abs($currentData->maxFailedMotion) < abs($user->moveData->moveDelta->y)){
                            $currentData->maxFailedMotion = $user->moveData->moveDelta->y;
                        }
                    }
                } else {
                    if($currentData->failedTime >= $currentData->maxTime){
                        if(++$this->preVL >= 5){
                            $percentage = ($currentData->maxFailedMotion / $currentData->motion) * 100;
                            $this->fail($user, "vertical percentage=$percentage");
                        }
                    } else {
                        $this->preVL -= $this->preVL;
                        $this->reward($user, 0.95);
                    }
                    $this->queue[0] = null;
                    array_shift($this->queue);
                    if(!empty($this->queue)){
                        // if the queue is not empty, make this process
                        // with the same move delta, but with the next motion in
                        // queue.
                        $this->handle($packet, $user);
                    }
                }
            }
        }
    }

}