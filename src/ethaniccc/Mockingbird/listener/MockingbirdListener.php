<?php

/*
$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\event\ClickEvent;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\event\PlayerHitPlayerEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
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
                $currentTime = microtime(true);
                if(!isset($this->previousClickTime[$playerName])){
                    $this->previousClickTime[$playerName] = $currentTime;
                    return;
                }
                $event = new ClickEvent($player, $this->previousClickTime[$playerName], $currentTime);
                $event->call();
                $this->previousClickTime[$playerName] = $currentTime;
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

    public function onJoin(PlayerJoinEvent $event) : void{
        $name = $event->getPlayer()->getName();
        if($event->getPlayer()->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
            $this->getPlugin()->registerStaff($name);
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if($damager instanceof Player && $damaged instanceof Player && !$event instanceof EntityDamageByChildEntityEvent){
            $event = new PlayerHitPlayerEvent($damager, $damaged);
            $event->call();
        }
    }

}