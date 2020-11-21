<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Event;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use stdClass;

class VelocityA extends Detection implements CancellableMovement{

    private $queue = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->suppression = false;
        $this->vlThreshold = 20;
        $this->lowMax = 4;
        $this->mediumMax = 8;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceTeleport <= 6){
                $this->queue = [];
                return;
            }
            if(count($this->queue) !== 0){
                $currentData = $this->queue[0];
                if(++$currentData->time <= $currentData->maxTime){
                    $AABB = clone $user->moveData->AABB;
                    $AABB->maxY += 0.1;
                    $blocksCollide = count($user->player->getLevel()->getCollisionBlocks($AABB, true)) > 0;
                    if($user->moveData->moveDelta->y < $currentData->motion * $this->getSetting("multiplier")
                    && $user->moveData->cobwebTicks >= 6 && $user->moveData->liquidTicks >= 6 && $currentData->motion >= 0.3 && $user->timeSinceStoppedFlight >= 20 && !$blocksCollide){
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

    public function handleEvent(Event $event, User $user): void{
        if($event instanceof EntityMotionEvent && $user->loggedIn){
            if(count($this->queue) > 5){
                return;
            }
            $info = new stdClass();
            $info->motion = $event->getVector()->y;
            $info->maxTime = (int) ($user->transactionLatency / 50) + 3;
            $info->time = 0;
            $info->failedTime = 0;
            $info->maxFailedMotion = 0;
            $this->queue[] = $info;
        }
    }

}