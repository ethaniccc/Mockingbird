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

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->suppression = false;
        $this->vlThreshold = 20;
        $this->lowMax = 4;
        $this->mediumMax = 8;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceMotion <= ($user->transactionLatency / 50) + 3 && $user->moveData->lastMotion !== null && $user->player->isAlive()){
                if($user->timeSinceTeleport <= 6){
                    $this->preVL = 0;
                }
                $expectedY = $user->moveData->lastMotion->y;
                if($expectedY < 0.2){
                    return;
                }
                $yDelta = $user->moveData->moveDelta->y;
                $expectedY *= $this->getSetting("multiplier");
                $scaledPercentage = ($yDelta / $expectedY) * 100;
                if($yDelta < $expectedY && $user->moveData->cobwebTicks >= 6 && $user->moveData->liquidTicks >= 6 && $user->moveData->blockAbove->getId() === 0
                && $user->timeSinceStoppedFlight >= 20){
                    if(++$this->preVL >= ($user->transactionLatency > 150 ? 40 : 30)){
                        $this->fail($user, "percentage(vertical)=$scaledPercentage% buffer={$this->preVL}");
                    }
                } else {
                    $this->preVL = max($this->preVL - 10, 0);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("percentage=$scaledPercentage% buffer={$this->preVL}");
                }
            }
        }
    }

}