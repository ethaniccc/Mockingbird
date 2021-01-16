<?php

namespace ethaniccc\Mockingbird\detections\movement\fly;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class FlyB
 * @package ethaniccc\Mockingbird\detections\movement\fly
 * FlyB checks if the user is not on ground (with two methods) and jumped. The first way
 * onGround is determined is by checking if the rounded Y position of the player is divisible
 * by 1/64 (check https://media.discordapp.net/attachments/756662753644511232/764979126783442994/unknown.png?width=1050&height=155).
 */
class FlyB extends Detection implements CancellableMovement{

    private $lastOnGround = true;
    private $modulo = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 10;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            // 0.00001 off???
            $this->modulo = fmod(round($user->moveData->location->y, 6) - 0.00001, 1 / 64);
            $this->lastOnGround = $this->modulo === 0.0;
        } elseif($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_JUMP){
            $rounded = round($user->moveData->location->y, 6) - 0.00001;
            if(!$this->lastOnGround && $user->moveData->offGroundTicks > 1 && !$user->player->isImmobile() && $user->moveData->blockBelow->getId() === 0 && $user->timeSinceTeleport >= 4){
                $this->fail($user, "modulo={$this->modulo} y=$rounded offGround={$user->moveData->offGroundTicks}");
            } else {
                $this->reward($user, 0.995);
            }
            if($this->isDebug($user)){
                $user->sendMessage("modulo={$this->modulo} y=$rounded offTicks={$user->moveData->offGroundTicks}");
            }
        }
    }

}