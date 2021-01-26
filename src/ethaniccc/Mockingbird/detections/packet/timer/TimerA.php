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
 * TimerA checks if a player is sending movement packets too fast while accounting for lag (this will no longer false on server lag).
 * The way TimerA accounts for lag is by using a concept of "balance". These movement packets should be sending 1 tick every time, so
 * every time we receive a movement packet, we get the time difference in ticks, and add that to the balance. From there, we subtract by
 * one, as one is the expected time it should be taking. If the balance goes below a threshold (-5), flag.
 */
class TimerA extends Detection{

    private $lastTime;
    private $balance = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->lowMax = 0; $this->mediumMax = 0;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $currentTime = microtime(true) * 1000;
            if(!$user->loggedIn){
                $this->balance = 0;
                return;
            }
            if($this->lastTime === null){
                $this->lastTime = $currentTime;
                return;
            }
            // convert the time difference into ticks (round this value to detect lower timer values).
            $timeDiff = round(($currentTime - $this->lastTime) / 50, 2);
            // there should be a one tick difference between the two packets
            $this->balance -= 1;
            // add the time difference between the two packet (this should be near one tick - which evens out the subtraction of one)
            $this->balance += $timeDiff;
            // if the balance is too low (the time difference is usually less than one tick)
            if($this->balance <= -5){
                $this->fail($user);
                $this->balance = 0;
            }
            if($this->isDebug($user)){
                $user->sendMessage("balance={$this->balance}");
            }
            $this->lastTime = $currentTime;
        }
    }

}