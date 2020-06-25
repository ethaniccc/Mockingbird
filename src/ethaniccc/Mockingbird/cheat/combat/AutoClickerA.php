<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class AutoClickerA extends Cheat{

    private $previousClick = [];
    private $allClicks = [];

    private $allDeviations = [];

    private $level = [];

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
            $this->level[$name] = 0;
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
        if(count($this->allClicks[$name]) < 10) return;
        $averageTime = array_sum($this->allClicks[$name]) / count($this->allClicks[$name]);
        $deviation = abs($time - $averageTime);
        array_push($this->allDeviations[$name], $deviation);
        if(count($this->allDeviations[$name]) < 10) return;
        $averageDeviation = array_sum($this->allDeviations[$name]) / count($this->allDeviations[$name]);
        if($averageDeviation < 9.5 && count($this->allDeviations[$name]) >= 35){
            $badDeviations = [];
            foreach($this->allDeviations[$name] as $number){
                if($number < 9.5) array_push($badDeviations, $number);
            }
            $badCount = count($badDeviations);
            if($badCount >= 25){
                $this->level[$name] = $this->level[$name] + 1;
                if($this->level[$name] >= 2.5){
                    $this->addViolation($name);
                    $data = [
                        "VL" => $this->getCurrentViolations($name),
                        "Ping" => $player->getPing()
                    ];
                    $this->notifyStaff($name, $this->getName(), $data);
                    $this->level[$name] = 1;
                }
            } else {
                $this->level[$name] = $this->level[$name] * 0.5;
            }
            $badDeviations = [];
        }
        if(count($this->allClicks[$name]) >= 45){
            unset($this->allClicks[$name]);
            $this->allClicks[$name] = [];
        }
        if(count($this->allDeviations[$name]) >= 55){
            unset($this->allDeviations[$name]);
            $this->allDeviations[$name] = [];
        }
    }

}