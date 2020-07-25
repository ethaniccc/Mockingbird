<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\event\ClickEvent;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player;
use pocketmine\Server;

class MockingbirdListener implements Listener{

    /** @var Mockingbird */
    private $plugin;
    /** @var array */
    private $previousPosition = [];
    /** @var array */
    private $previousClickTime = [];

    public function __construct(Mockingbird $plugin){
        $this->plugin = $plugin;
    }

    public function getPlugin() : Mockingbird{
        return $this->plugin;
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $playerName = $event->getPlayer()->getName();

        if($packet instanceof MovePlayerPacket){
            if(!isset($this->previousPosition[$playerName])){
                $this->previousPosition[$playerName] = $packet->position;
                return;
            }
            $event = new MoveEvent($player, $this->previousPosition[$playerName], $packet->position, $packet->onGround, $packet->mode);
            $event->call();
            $this->previousPosition[$playerName] = $packet->position;
        } elseif($packet instanceof InventoryTransactionPacket){
            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
                $currentTime = Server::getInstance()->getTick() * 50;
                $ids = [];
                foreach($packet->trData as $data){
                    if(is_int($data)){
                        array_push($ids, $data);
                    }
                }
                if(!isset($this->previousClickTime[$playerName])){
                    $this->previousClickTime[$playerName] = $currentTime;
                    return;
                }
                $event = new ClickEvent($player, $this->previousClickTime[$playerName], $currentTime);
                $event->call();
                $this->previousClickTime[$playerName] = $currentTime;
                $entityHitID = $ids[0];
                $damaged = $player->getLevel()->getEntity($entityHitID);
                if($damaged instanceof Player){
                    $event = new PlayerHitPlayerEvent($player, $damaged);
                    $event->call();
                }
            }
        } elseif($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE){
                $currentTime = Server::getInstance()->getTick() * 50;
                if(!isset($this->previousClickTime[$playerName])){
                    $this->previousClickTime[$playerName] = $currentTime;
                    return;
                }
                $event = new ClickEvent($player, $this->previousClickTime[$playerName], $currentTime);
                $event->call();
                $this->previousClickTime[$playerName] = $currentTime;
            }
        }
    }

}