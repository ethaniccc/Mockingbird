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
 * VelocityA checks if the user's vertical velocity is lower than normal. This can detect 98%
 * vertical velocity on Horion (and ~94% on other clients).
 */
class VelocityA extends Detection implements CancellableMovement{

    /** @var Vector3|null */
    private $receivedMotion;
    private $pendingMotions = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->suppression = false;
        $this->vlSecondCount = 20;
        $this->lowMax = 4;
        $this->mediumMax = 8;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket && $this->receivedMotion !== null){
            if($this->receivedMotion->y > 0){
                // TODO: Account for more scenarios where falses could occur.
                $currentYDelta = $user->moveData->moveDelta->y;
                $percentage = ($currentYDelta / $this->receivedMotion->y) * 100;
                // against walls this check for some reason will false at ~99.9999%, what the fuck
                if($percentage < 99.9999 && $user->moveData->blockAbove->getId() === 0 && $user->moveData->liquidTicks >= 10 && $user->moveData->cobwebTicks >= 10
                    && $user->moveData->levitationTicks >= 10 && $user->timeSinceTeleport >= 10 && $user->timeSinceStoppedFlight >= 10 && $user->timeSinceStoppedGlide >= 10){
                    if(++$this->preVL >= 1){
                        $roundedPercentage = round($percentage, 3); $roundedBuffer = round($this->preVL, 2);
                        $this->fail($user, "(A) percentage=$percentage% buff={$this->preVL}", "pct=$roundedPercentage% buff=$roundedBuffer");
                    }
                } else {
                    $this->reward($user, $user->transactionLatency > 400 ? 0.4 : 0.2, false);
                    $this->preVL = max($this->preVL - 0.75, 0);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("percentage=$percentage% latency={$user->transactionLatency} buff={$this->preVL}");
                }
            }
            $this->receivedMotion = null;
        } elseif($packet instanceof NetworkStackLatencyPacket){
            $motion = $this->pendingMotions[$packet->timestamp] ?? null;
            if($motion !== null){
                $this->receivedMotion = $motion;
                unset($this->pendingMotions[$packet->timestamp]);
            }
        }
    }

    public function handleSend(DataPacket $packet, User $user): void{
        if($packet instanceof SetActorMotionPacket && $user->player !== null && $packet->entityRuntimeId === $user->player->getId()){
            $pk = new NetworkStackLatencyPacket();
            $pk->needResponse = true; $pk->timestamp = ($timestamp = mt_rand(1, 10000000) * 1000);
            $user->player->dataPacket($pk);
            $this->pendingMotions[$timestamp] = $packet->motion;
        }
    }

    public function canHandleSend(): bool{
        return true;
    }

}