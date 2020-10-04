<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class VelocityA extends Detection implements MovementDetection{

    private $queue = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function process(DataPacket $packet, User $user): void{
        if($packet instanceof MotionPacket && $user->loggedIn){
            if(count($this->queue) === 5){
                array_shift($this->queue);
            }
            $info = new \stdClass();
            $info->motion = $packet->motionY;
            $info->maxTime = (int) ($user->player->getPing() / 50) + 3;
            $info->time = 0;
            $info->failedTime = 0;
            $info->maxFailedMotion = 0;
            $this->queue[] = $info;
        } elseif($packet instanceof MovePlayerPacket){
            if($user->moveDelta === null){
                return;
            }
            if($packet->mode !== MovePlayerPacket::MODE_NORMAL){
                $this->queue = [];
                return;
            }
            if(!empty($this->queue)){
                $currentData = $this->queue[0];
                if(++$currentData->time <= $currentData->maxTime){
                    if($user->moveDelta->y < $currentData->motion * $this->getSetting("multiplier")
                    && $user->blockAbove === null){
                        ++$currentData->failedTime;
                        if(abs($currentData->maxFailedMotion) < abs($user->moveDelta->y)){
                            $currentData->maxFailedMotion = $user->moveDelta->y;
                        }
                    } else {
                        $this->queue[0] = null;
                        array_shift($this->queue);
                    }
                } else {
                    if($currentData->failedTime >= $currentData->maxTime){
                        if(++$this->preVL >= 10){
                            $this->fail($user, "{$user->player->getName()}: eD: {$currentData->motion}, mYD: {$currentData->maxFailedMotion}, mT: {$currentData->maxTime}");
                        }
                    } else {
                        $this->preVL = 0;
                        $this->reward($user, 0.95);
                    }
                    $this->queue[0] = null;
                    array_shift($this->queue);
                    if(!empty($this->queue)){
                        // if the queue is not empty, make this process
                        // with the same move delta, but with the next motion in
                        // queue.
                        $this->process($packet, $user);
                    }
                }
            }
        }
    }

}