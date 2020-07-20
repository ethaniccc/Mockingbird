<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class Timer extends Cheat implements StrictRequirements{

    /** @var array */
    private $lastSentTime = [];

    /** @var array */
    private $previousTimeDiff = [];

    /** @var array */
    private $balance = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredPing(10000);
        $this->setRequiredTPS(20.0);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();

        if($packet instanceof MovePlayerPacket){
            $currentTime = $this->getServer()->getTick() * 50;
            if(!isset($this->lastSentTime[$name])){
                $this->lastSentTime[$name] = $currentTime;
                return;
            }
            $timeDiff = round($currentTime - $this->lastSentTime[$name], 2);
            if(!isset($this->balance[$name])){
                $this->balance[$name] = 0;
            }
            $this->balance[$name] = $this->balance[$name] - 50 + $timeDiff;
            if(isset($this->previousTimeDiff[$name])){
                if($this->previousTimeDiff[$name] > 100 && $timeDiff <= 100){
                    $this->balance[$name] = 0;
                }
            }
            if($this->balance[$name] <= -250){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
            $this->lastSentTime[$name] = $currentTime;
            $this->previousTimeDiff[$name] = $timeDiff;
        }
    }

}