<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class BadPacketE
 * @package ethaniccc\Mockingbird\detections\packet\badpackets
 * BadPacketE checks for invalid move deltas given in received PlayerAuthInput packets. This behavior was found
 * on Toolbox's freecam and can be considered as sending invalid packets (which is why this check is a badpacket check).
 */
class BadPacketE extends Detection{

    private $lastDiff = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            // -0.07840000092983246 is a predicted value the client thinks it's going to move next.
            // after doing some digging, it seems that movement packet deltas (all I know is for PlayerAuthInput, not sure for MovePlayer)
            // are prediction deltas of where the client it's next move is going to be. for all I know, with PlayerAuthInput, the prediction always
            // assumes that the client is off the ground.
            // as to why -0.07840000092983246 is the prediction, see FlyA:
            // ($lastYDelta - 0.08) * 0.980000012
            // if the client is on the ground, $lastYDelta is probably 0.
            if(($diff = ($packet->getDelta()->y + 0.07840000092983246)) < -0.34 && $this->lastDiff > $diff && abs($user->moveData->moveDelta->y) < 0.00001 /* precision go brrrr? */ && $user->timeSinceJoin >= 100 && $user->timeSinceTeleport >= 10){
                // if this behavior continues consistently
                if(++$this->preVL >= 40){
                    $this->fail($user, 'moveDeltaY=' . $user->moveData->moveDelta->y . ' received=' . $packet->getDelta()->y . ' diff=' . $diff . ' lastDiff=' . $this->lastDiff);
                }
            }
            if($this->isDebug($user)){
                $user->sendMessage('moveDeltaY=' . $user->moveData->moveDelta->y . ' received=' . $packet->getDelta()->y . ' diff=' . $diff . ' lastDiff=' . $this->lastDiff);
            }
            $this->lastDiff = $diff;
        }
    }

}