<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class AutoClickerB extends Cheat{

    private $cps = [];

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

    private function clickCheck(Player $player) : void{
        // Reference: https://github.com/luca28pet/PreciseCpsCounter/blob/master/src/luca28pet/PreciseCpsCounter/Main.php
        $name = $player->getName();
        if(!isset($this->cps[$name])) $this->cps[$name] = [];
        array_unshift($this->cps[$name], microtime(true));
        if(count($this->cps[$name]) >= 100){
            array_pop($this->cps[$name]);
        }
        if(empty($this->cps[$name])) return;
        $deltaTime = 1.0;
        $currentTime = microtime(true);
        $cps = round(count(array_filter($this->cps[$name], static function(float $t) use ($deltaTime, $currentTime) : bool{
                return ($currentTime - $t) <= $deltaTime;
        })) / $deltaTime, 1);
        if($cps >= 22){
            $this->addViolation($name);
            $data = [
                "VL" => self::getCurrentViolations($name),
                "CPS" => $cps,
                "Ping" => $player->getPing()
            ];
            $this->notifyStaff($name, $this->getName(), $data);
        }
    }

}