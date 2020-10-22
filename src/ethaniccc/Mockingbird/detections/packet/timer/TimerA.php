<?php

namespace ethaniccc\Mockingbird\detections\packet\timer;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class TimerA extends Detection{

    private $lastTime;
    private $balance = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $currentTime = Server::getInstance()->getTick() * 50;
            if($this->lastTime === null){
                $this->lastTime = $currentTime;
                return;
            }
            $timeDiff = $currentTime - $this->lastTime;
            $this->balance -= 50;
            $this->balance += $timeDiff;
            if($this->balance <= -250){
                $this->fail($user);
                $this->balance = 0;
            }
            $this->lastTime = $currentTime;
        }
    }

}