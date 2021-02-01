<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class BadPacketB
 * @package ethaniccc\Mockingbird\detections\packet\badpackets
 * BadPacketB checks if the user is consistency sending MovePlayer packets rather than PlayerAuthInput packets.
 * The client can still send MovePlayer packets, but not constantly.
 */
class BadPacketB extends Detection{

    private $lastTime;
    private $ticks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 5;
        $this->lowMax = 1;
        $this->mediumMax = 2;
        $this->lastTime = $this->ticks;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof MovePlayerPacket){
            $speed = $this->ticks - $this->lastTime;
            if($speed < 2){
                $this->fail($user, "speed=$speed");
            }
            $this->lastTime = $this->ticks;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
            $this->reward($user, 0.999);
        }
    }

}