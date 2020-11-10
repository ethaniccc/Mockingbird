<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\player\cheststeal\ChestStealerA;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\packets\BlockPlacePacket;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\processing\Processor;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\ChestInventory;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\Player;
use pocketmine\Server;

class MockingbirdListener implements Listener{

    public function __construct(){
        Server::getInstance()->getPluginManager()->registerEvents($this, Mockingbird::getInstance());
    }

    public function onPacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof LoginPacket){
            $user = new User($player);
            UserManager::getInstance()->register($user);
        }

        $user = UserManager::getInstance()->get($player);
        if($user !== null){
            foreach($user->processors as $processor){
                if($processor instanceof Processor){
                    $processor->process($packet);
                }
            }
            foreach($user->detections as $check){
                if($check instanceof Detection){
                    $check->handle($packet, $user);
                }
            }
        }

        if($packet instanceof PlayerAuthInputPacket){
            // make debug *insert IdotHub :shut: emoji*
            $event->setCancelled();
        }
    }

    public function onPacketSend(DataPacketSendEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof StartGamePacket){
            $packet->isMovementServerAuthoritative = true;
        }
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        $user = UserManager::getInstance()->get($event->getPlayer());
        if($user === null){
            throw new \UnexpectedValueException("{$event->getPlayer()->getName()} was not registered");
        } else {
            $user->loggedIn = true;
            if($user->player->hasPermission("mockingbird.alerts") && Mockingbird::getInstance()->getConfig()->get("alerts_default")){
                $user->alerts = true;
            }
            $pk = new NetworkStackLatencyPacket();
            $pk->timestamp = 1000;
            $pk->needResponse = true;
            $user->player->dataPacket($pk);
            $user->lastSentNetworkLatencyTime = microtime(true);
        }
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $user = UserManager::getInstance()->get($entity);
            $user->timeSinceMotion -= $user->timeSinceMotion > 0 ? $user->timeSinceMotion : 3;
            $user->moveData->lastMotion = $event->getVector();
            $motionPK = new MotionPacket($event);
            foreach($user->detections as $check){
                if($check instanceof Detection){
                    $check->handle($motionPK, $user);
                }
            }
        }
    }

    public function onTeleport(EntityTeleportEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $user = UserManager::getInstance()->get($entity);
            if($user !== null){
                $user->timeSinceTeleport = 0;
            }
        }
    }

    public function onPlacedBlock(BlockPlaceEvent $event) : void{
        $user = UserManager::getInstance()->get($event->getPlayer());
        if($user !== null){
            $pk = new BlockPlacePacket($event);
            foreach($user->processors as $processor){
                if($processor instanceof Processor){
                    $processor->process($pk);
                }
            }
            foreach($user->detections as $check){
                if($check instanceof Detection){
                    $check->handle($pk, $user);
                }
            }
        }
    }

    // I hate it here
    public function onTransaction(InventoryTransactionEvent $event) : void{
        $user = UserManager::getInstance()->get($event->getTransaction()->getSource());
        $check = $user->detections["ChestStealerA"] ?? null;
        if($check instanceof ChestStealerA){
            foreach($event->getTransaction()->getInventories() as $inventory){
                if($inventory instanceof ChestInventory){
                    $continue = true;
                }
            }
            if(isset($continue)){
                ++$check->transactions;
            }
        }
    }

}