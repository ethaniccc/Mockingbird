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
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\TickSyncPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MockingbirdListener implements Listener{

    private $plugin;
    private $previousPosition = [];
    private $previousClickTime = [];
    private $clicks = [];

    public function __construct(Mockingbird $plugin){
        $this->plugin = $plugin;
    }

    public function getPlugin() : Mockingbird{
        return $this->plugin;
    }

    /**
     * @param DataPacketReceiveEvent $event
     * @priority HIGHEST
     */
    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $playerName = $event->getPlayer()->getName();

        if($packet instanceof MovePlayerPacket){
            if(!isset($this->previousPosition[$playerName])){
                $this->previousPosition[$playerName] = $packet->position;
                return;
            }
            $event = new MoveEvent($player, $this->previousPosition[$playerName], $packet->position, $packet->onGround, $packet->mode, $packet->yaw, $packet->pitch);
            $event->call();
            $this->previousPosition[$playerName] = $packet->position;
            $this->getPlugin()->getUserManager()->get($player)->handleMove($event);
        } elseif($packet instanceof InventoryTransactionPacket){
            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
                $currentTime = microtime(true);
                if(!isset($this->previousClickTime[$playerName])){
                    $this->previousClickTime[$playerName] = $currentTime;
                    return;
                }
                $cps = $this->getCPS($player);
                $event = new ClickEvent($player, $this->previousClickTime[$playerName], $currentTime, $cps);
                $event->call();
                $this->previousClickTime[$playerName] = $currentTime;
                if($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
                    $this->getPlugin()->getUserManager()->get($player)->setAttackPosition($packet->trData->playerPos);
                }
            }
        } elseif($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE){
                $currentTime = microtime(true);
                if(!isset($this->previousClickTime[$playerName])){
                    $this->previousClickTime[$playerName] = $currentTime;
                    return;
                }
                $cps = $this->getCPS($player);
                $event = new ClickEvent($player, $this->previousClickTime[$playerName], $currentTime, $cps);
                $event->call();
                $this->previousClickTime[$playerName] = $currentTime;
            }
        } elseif($packet instanceof LoginPacket){
            // if EditionFaker is disabled, or somehow EditionFaker is bypassed, this can be used as the AntiCheat's disabler (for some detections)!
            $isMobile = in_array($packet->clientData["DeviceOS"], [DeviceOS::ANDROID, DeviceOS::IOS, DeviceOS::AMAZON]);
            $this->getPlugin()->getUserManager()->register($player, $isMobile, $packet);
        }
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $this->getPlugin()->getUserManager()->get($event->getPlayer())->handleJump($event);
    }

    /**
     * @param PlayerJoinEvent $event
     * @priority HIGHEST
     */
    public function onJoin(PlayerJoinEvent $event) : void{
        $name = $event->getPlayer()->getName();
        if($event->getPlayer()->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
            $this->getPlugin()->registerStaff($name);
        }
        $this->getPlugin()->getUserManager()->get($event->getPlayer())->handleJoin($event);
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * @priority HIGHEST
     */
    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if($damager instanceof Player && $damaged instanceof Player && !$event instanceof EntityDamageByChildEntityEvent && !$event->isCancelled()){
            $this->getPlugin()->getUserManager()->get($damaged)->handleHit($event);
            $hitEvent = new PlayerHitPlayerEvent($damager, $damaged, $event->getAttackCooldown(), $event->getKnockBack());
            $hitEvent->call();
            if($hitEvent->isCancelled()){
                $event->setCancelled();
            }
        }
    }

    /**
     * @param EntityDamageEvent $event
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->getPlugin()->getUserManager()->get($entity)->handleDamage($event);
        }
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $this->getPlugin()->getUserManager()->get($entity)->handleMotion($event);
        }
    }

    private function getCPS(Player $player){
        // ahahahaa.... copy pasta thx John: https://github.com/Bavfalcon9/Mavoric/blob/stable/src/Bavfalcon9/Mavoric/Core/Detections/AutoClicker.php
        $name = $player->getName();
        if(!isset($this->clicks[$name])){
            $this->clicks[$name] = [];
        }
        $currentTime = microtime(true);
        array_unshift($this->clicks[$name], $currentTime);
        if(!empty($this->clicks[$name])){
            return count(array_filter($this->clicks[$name], function(float $t) use ($currentTime){
                return $currentTime - $t <= 1;
            }));
        }
        return 0;
    }

}
