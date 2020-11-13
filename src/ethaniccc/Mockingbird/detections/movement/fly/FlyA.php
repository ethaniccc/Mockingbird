<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class FlyA extends Detection implements CancellableMovement{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(!$user->player->isAlive() || !$user->loggedIn){
                return;
            }
            $yDelta = $user->moveData->moveDelta->y;
            $lastYDelta = $user->moveData->lastMoveDelta->y;
            $expectedYDelta = ($lastYDelta - 0.08) * 0.980000019073486;
            $equalness = abs($yDelta - $expectedYDelta);
            if($equalness > $this->getSetting("max_breach")
            && abs($expectedYDelta) > 0.05
            && $user->moveData->offGroundTicks >= 10 && $user->timeSinceTeleport > 5
            && $user->timeSinceJoin >= 20
            && $user->timeSinceMotion >= 5
            && !$user->player->isFlying()
            && !$user->player->getAllowFlight()
            && !$user->player->isSpectator()
            && $user->moveData->location->y > 0 && $user->moveData->blockAbove->getId() === 0
            && $user->player->getArmorInventory()->getChestplate()->getId() !== ItemIds::ELYTRA
            && !$user->player->isImmobile()){
                if(++$this->preVL >= 3){
                    $this->fail($user, "yD=$yDelta, eD=$expectedYDelta, eq=$equalness");
                }
            } else {
                if($user->moveData->offGroundTicks >= 6){
                    $this->preVL *= 0.8;
                    $this->reward($user, 0.995);
                }
            }
        }
    }

}