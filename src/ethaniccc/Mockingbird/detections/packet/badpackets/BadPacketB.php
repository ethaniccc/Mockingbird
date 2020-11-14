<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class BadPacketB extends Detection{

    private $lastTime;
    private $ticks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 5;
        $this->lowMax = 1;
        $this->mediumMax = 2;
        $this->lastTime = $this->ticks;
    }

    public function handle(DataPacket $packet, User $user): void{
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