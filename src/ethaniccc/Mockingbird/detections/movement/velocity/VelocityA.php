<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Event;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\Network;
use stdClass;

/**
 * Class VelocityA
 * @package ethaniccc\Mockingbird\detections\movement\velocity
 * VelocityA checks if the user's vertical velocity is lower than normal. This detection uses
 * NetworkStackLatency to confirm the client has received the SetActorMotion packet and then waits for
 * the next movement packet.
 */
class VelocityA extends Detection implements CancellableMovement{

    private $receivedYMotion;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->suppression = false;
        $this->vlSecondCount = 20;
        $this->lowMax = 4;
        $this->mediumMax = 8;
        $this->receivedYMotion = 0.0;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->timeSinceMotion === 1){
                $this->receivedYMotion = $user->moveData->lastMotion->y;
            }
            if($user->timeSinceTeleport <= 10){
                $this->receivedYMotion = 0;
                $this->preVL = 0;
            }
            if($this->receivedYMotion > 0.005){
                $currentYDelta = $user->moveData->moveDelta->y;
                $percentage = ($currentYDelta / $this->receivedYMotion) * 100;
                // against walls this check for some reason will false at ~99.9999%, what the fuck
                // on some KB plugins, this can false at ~99.99%, adjust your config accordingly.
                $collisionAABB = clone $user->moveData->AABB;
                $collisionAABB->minY = $collisionAABB->maxY;
                $collisionAABB->maxY += 0.2;
                $collisionAABB->grow(-0.2, 0, -0.2);
                if($percentage < $this->getSetting('percentage', 99.99) && count($user->player->getLevel()->getCollisionBlocks($collisionAABB, true)) === 0 && $user->moveData->liquidTicks >= 10 && $user->moveData->cobwebTicks >= 10
                    && $user->moveData->levitationTicks >= 10 && $user->timeSinceStoppedFlight >= 10 && $user->timeSinceStoppedGlide >= 10){
                    if(++$this->preVL >= 6){
                        $roundedPercentage = round($percentage, 3); $roundedBuffer = round($this->preVL, 2);
                        $this->fail($user, "(A) percentage=$percentage% buff={$this->preVL}", "pct=$roundedPercentage% buff=$roundedBuffer");
                        $this->preVL = min($this->preVL, 30);
                    }
                } else {
                    $this->reward($user, $user->transactionLatency > 400 ? 0.4 : 0.2);
                    $this->preVL = max($this->preVL - 0.5, 0);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("percentage=$percentage% latency={$user->transactionLatency} buff={$this->preVL}");
                }
                $this->receivedYMotion = ($this->receivedYMotion - 0.08) * 0.980000012;
            }
        }
    }

}