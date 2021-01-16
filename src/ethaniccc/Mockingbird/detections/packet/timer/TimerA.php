<?php

namespace ethaniccc\Mockingbird\detections\packet\timer;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

/**
 * Class TimerA
 * @package ethaniccc\Mockingbird\detections\packet\timer
 * TimerA checks if the user is sending too many PlayerAuthInput packets. This check
 * will false when the server lags, but is here nevertheless.
 */
class TimerA extends Detection{

    private $lastTime;
    private $balance = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 40;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if(!$user->loggedIn){
                $this->balance = 0;
                return;
            }
            if(Server::getInstance()->getTicksPerSecond() < 20){
                return;
            }
            $currentTime = Server::getInstance()->getTick() * 50;
            if($this->lastTime === null){
                $this->lastTime = $currentTime;
                return;
            }
            $timeDiff = $currentTime - $this->lastTime;
            $this->balance -= 50;
            $this->balance += $timeDiff;
            if($this->balance <= -500){
                $tps = Server::getInstance()->getTicksPerSecond();
                $this->fail($user, "tps=$tps");
                $this->balance = 0;
            }
            $this->lastTime = $currentTime;
        }
    }

}