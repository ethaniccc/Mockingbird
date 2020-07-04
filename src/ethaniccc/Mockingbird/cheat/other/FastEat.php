<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Consumable;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;


class FastEat extends Cheat{

    private $startEatTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof ActorEventPacket){
            if($packet->event === ActorEventPacket::EATING_ITEM){
                if(!isset($this->startEatTick[$name])){
                    $this->startEatTick[$name] = $this->getServer()->getTick();
                }
            }
        }
    }

    public function completeEat(PlayerItemConsumeEvent $event){
        $item = $event->getItem();
        $player = $event->getPlayer();
        $name = $player->getName();
        if($item instanceof Consumable){
            if($this->playerIsEating($player)){
                $timeDiff = $this->getServer()->getTick() - $this->startEatTick[$name];
                if($timeDiff < 24){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
            }
            unset($this->startEatTick[$name]);
        }
    }

    private function playerIsEating(Player $player) : bool{
        if(isset($this->startEatTick[$player->getName()])){
            if($this->getServer()->getTick() - $this->startEatTick[$player->getName()] > 25) unset($this->startEatTick[$player->getName()]);
        }
        return isset($this->startEatTick[$player->getName()]) ? $this->getServer()->getTick() - $this->startEatTick[$player->getName()] <= 25 : false;
    }

}