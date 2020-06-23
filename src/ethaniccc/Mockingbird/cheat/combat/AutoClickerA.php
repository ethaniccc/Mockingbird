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
    private $allClicks = [];

    private $allDeviations = [];

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
            $this->allClicks[$name] = [];
            $this->allDeviations[$name] = [];
            return;
        }
        $currentTime = microtime(true) * 1000;
        $time = $currentTime - $this->previousClick[$name];
        if($time > 1000){
            $this->previousClick[$name] = microtime(true) * 1000;
            return;
        }
        array_push($this->allClicks[$name], $time);
        $this->previousClick[$name] = microtime(true) * 1000;
        if(count($this->allClicks[$name]) < 5) return;
        $averageTime = array_sum($this->allClicks[$name]) / count($this->allClicks[$name]);
        $deviation = abs($time - $averageTime);
        array_push($this->allDeviations[$name], $deviation);
        if(count($this->allDeviations[$name]) < 5) return;
        $averageDeviation = array_sum($this->allDeviations[$name]) / count($this->allDeviations[$name]);
        //$this->getServer()->broadcastMessage("$averageDeviation");
        if($averageDeviation < 10 && count($this->allDeviations[$name]) > 30){
            $badDeviations = [];
            foreach($this->allDeviations[$name] as $number){
                if($number < 10) array_push($badDeviations, $number);
            }
            if(count($badDeviations) >= 15){
                $this->addViolation($name);
                $data = [
                    "VL" => $this->getCurrentViolations($name),
                    "Ping" => $player->getPing()
                ];
                $this->notifyStaff($name, $this->getName(), $data);
            }
            $badDeviations = [];
        }
        if(count($this->allClicks[$name]) >= 60){
            unset($this->allClicks[$name]);
            $this->allClicks[$name] = [];
        }
        if(count($this->allDeviations[$name]) >= 50){
            unset($this->allDeviations[$name]);
            $this->allDeviations[$name] = [];
        }
    }

}