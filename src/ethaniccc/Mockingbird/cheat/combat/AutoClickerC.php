<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\Player;

class AutoClickerC extends Cheat{

    private $lastClick = [];

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
        $name = $player->getName();
        if(!isset($this->lastClick[$name])){
            $this->lastClick[$name] = microtime(true) * 1000;
            return;
        }
        $currentTime = microtime(true) * 1000;
        $speed = $currentTime - $this->lastClick[$name];
        if($speed <= 1.1){
            $this->addViolation($name);
            $data = [
                "VL" => $this->getCurrentViolations($name),
                "Ping" => $player->getPing()
            ];
            $this->notifyStaff($name, $this->getName(), $data);
        }
        $this->lastClick[$name] = $currentTime;
    }

}
