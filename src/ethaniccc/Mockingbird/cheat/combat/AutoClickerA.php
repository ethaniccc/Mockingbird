<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\utils\TextFormat;

class AutoClickerA extends Cheat{

    private $previousClick = [];
    private $clickAverage = [];
    private $deviationAverage = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof InventoryTransactionPacket){
            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) $this->clickCheck($event->getPlayer());
        } elseif($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) $this->clickCheck($event->getPlayer());
        } elseif($packet instanceof PlayerActionPacket){
            if($packet->action === PlayerActionPacket::ACTION_START_BREAK) $this->clickCheck($event->getPlayer());
        }
    }

    private function clickCheck(Player $player){
        $name = $player->getName();
        if(!isset($this->previousClick[$name])){
            $this->previousClick[$name] = microtime(true) * 1000;
            $this->clickAverage[$name] = [];
            $this->deviationAverage[$name] = [];
            return;
        }
        $currentTime = microtime(true) * 1000;
        $time = $currentTime - $this->previousClick[$name];
        if($time > 1000){
            $this->previousClick[$name] = $currentTime;
            return;
        }
        array_push($this->clickAverage[$name], $time);
        $averageTime = array_sum($this->clickAverage[$name]) / count($this->clickAverage[$name]);
        $deviation = abs($time - $averageTime);
        array_push($this->deviationAverage[$name], $deviation);
        $averageDevitation = array_sum($this->deviationAverage[$name]) / count($this->deviationAverage[$name]);
        $this->getServer()->broadcastMessage("$averageDevitation");
        if(count($this->deviationAverage[$name]) > 10 && $averageDevitation < 10){
            $this->getServer()->broadcastMessage(TextFormat::BOLD . TextFormat::RED . "CHEATER!!!!");
        }
        $this->previousClick[$name] = $currentTime;
        if(count($this->clickAverage[$name]) === 50) $this->clickAverage[$name] = [];
        if(count($this->deviationAverage[$name]) === 50) $this->deviationAverage[$name] = [];
    }

}