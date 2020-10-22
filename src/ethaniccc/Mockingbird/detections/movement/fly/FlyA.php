<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class FlyA extends Detection implements MovementDetection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(!$user->player->isAlive()){
                return;
            }
            $yDelta = $user->moveDelta->y;
            $lastYDelta = $user->lastMoveDelta->y;
            $expectedYDelta = ($lastYDelta - 0.08) * 0.980000019073486;
            $equalness = abs($yDelta - $expectedYDelta);
            if($equalness > $this->getSetting("max_breach")
            && abs($expectedYDelta) > 0.05
            && $user->offGroundTicks >= 10 && $user->timeSinceTeleport > 5
            && $user->timeSinceJoin >= 20
            && $user->timeSinceMotion >= 5
            && !$user->player->isFlying()
            && !$user->player->getAllowFlight()
            && !$user->player->isSpectator()
            && $user->location->y > 0 && $user->blockAbove === null
            && $user->player->getArmorInventory()->getChestplate()->getId() !== ItemIds::ELYTRA){
                if(++$this->preVL >= 3){
                    $this->fail($user, "{$user->player->getName()}: yD: $yDelta, eD: $expectedYDelta, eq: $equalness");
                }
            } else {
                if($user->offGroundTicks >= 3){
                    $this->preVL *= 0.8;
                    $this->reward($user, 0.995);
                }
            }
        }
    }

}