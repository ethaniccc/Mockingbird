<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\server\DataPacketSendEvent;

class Timer extends Cheat implements StrictRequirements{

    /** @var array */
    private $playerBalance, $playerPreviousTimeDiff, $playerLastSentTick = [];
    /** @var array */
    private $serverBalance = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(20.0);
        $this->setRequiredPing(10000);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->playerBalance[$name])){
            $this->playerBalance[$name] = 0;
        }
        if(!isset($this->playerLastSentTick[$name])){
            $this->playerLastSentTick[$name] = $this->getServer()->getTick();
            return;
        }

        $time = ($this->getServer()->getTick() - $this->playerLastSentTick[$name]) * 50;
        $this->playerBalance[$name] += 50;
        $this->playerBalance[$name] -= $time;

        if(isset($this->playerPreviousTimeDiff[$name])){
            // the player decided not to move and not cause of lag.
            if($this->playerPreviousTimeDiff[$name] > 100 && ($time <= 100 && $time >= 50)){
                $this->playerBalance[$name] = 0;
            }
        }

        if($this->playerBalance[$name] >= 500){
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            $this->playerBalance[$name] = 0;
        }

        $this->playerLastSentTick[$name] = $this->getServer()->getTick();
        $this->playerPreviousTimeDiff[$name] = $time;
    }

    public function sendPacket(DataPacketSendEvent $event) : void{
        // TODO: Add a server-side balance to compare to the player's balance.
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();
    }

}