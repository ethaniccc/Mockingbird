<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class FlyA
 * @package ethaniccc\Mockingbird\detections\movement\fly
 * FlyA predicts what the user's Y distance should be, and compares that to the current Y movement the user gives.
 * If the difference between the predicted Y distance and the given Y distance is too high, flag.
 */
class FlyA extends Detection implements CancellableMovement{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(!$user->player->isAlive() || !$user->loggedIn){
                return;
            }
            $yDelta = $user->moveData->moveDelta->y;
            $lastYDelta = $user->moveData->lastMoveDelta->y;
            // prediction (see https://github.com/eldariamc/client/blob/c01d23eb05ed83abb4fee00f9bf603b6bc3e2e27/src/main/java/net/minecraft/entity/EntityLivingBase.java#L1682-L1687)
            $expectedYDelta = ($lastYDelta - 0.08) * 0.980000019073486;
            $equalness = abs($yDelta - $expectedYDelta);
            if($equalness > $this->getSetting("max_breach")
            && abs($expectedYDelta) > 0.005
            && $user->moveData->offGroundTicks >= 10 && $user->timeSinceTeleport > 5
            && $user->timeSinceJoin >= 200
            && $user->timeSinceMotion >= 5
            && $user->moveData->ticksSinceInVoid >= 10 && $user->moveData->blockAbove->getId() === 0 && $user->moveData->blockBelow->getId() === 0
            && $user->timeSinceStoppedFlight >= 10 && $user->timeSinceLastBlockPlace >= 10
            && $user->moveData->cobwebTicks >= 15 && $user->moveData->liquidTicks >= 15
            && $user->timeSinceStoppedGlide >= 10 && $user->moveData->levitationTicks >= 5 && $user->hasReceivedChunks){
                if(++$this->preVL >= 3){
                    $this->fail($user, "yD=$yDelta, eD=$expectedYDelta, eq=$equalness");
                }
            } else {
                if($user->moveData->offGroundTicks >= 6 && $user->hasReceivedChunks){
                    $this->preVL *= 0.8;
                    $this->reward($user, 0.995);
                }
            }
            if($this->isDebug($user)){
                $user->sendMessage("yDelta=$yDelta predicted=$expectedYDelta diff=$equalness");
            }
        }
    }

}