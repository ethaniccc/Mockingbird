<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\block\Ice;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class SpeedB extends Detection implements MovementDetection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function process(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            if($user->blockAbove !== null){
                return;
            }
            if($user->moveDelta === null){
                return;
            }
            $xzDistance = hypot($user->moveDelta->x, $user->moveDelta->z);
            $maxDist = $user->serverOnGround ? $this->getSetting("max_speed_on_ground") : $this->getSetting("max_speed_off_ground");
            if($user->blockBelow instanceof Ice){
                $maxDist *= 5 / 3;
            }
            if($user->player->getEffect(1) !== null){
                $effectLevel = $user->player->getEffect(1)->getAmplifier() + 1;
                $maxDist += 0.2 * $effectLevel;
            }
            if($xzDistance > $maxDist
            && $user->timeSinceMotion >= 20
            && !$user->player->getInventory()->getItemInHand()->hasEnchantment(Enchantment::RIPTIDE)
            && !$user->player->isFlying()){
                if(++$this->preVL >= 3){
                    $this->fail($user, "{$user->player->getName()}: d: $xzDistance, eD: $maxDist");
                }
            } else {
                $this->preVL *= 0.8;
                $this->reward($user, 0.995);
            }
        }
    }

}