<?php

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

class BadPacketA extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(1);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $packet = $event->getPacket();
        if($packet instanceof MovePlayerPacket){
            if(abs($packet->pitch) > 90){
                $this->addViolation($name);
            }
        }
    }

}