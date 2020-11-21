<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
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
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\UnknownPacket;
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
                    $start = microtime(true);
                    $processor->process($packet);
                    $time = microtime(true) - $start;
                    if($time > 0.01){
                        Mockingbird::getInstance()->debugTask->addData(get_class($processor) . " took too long to process: $time");
                    }
                }
            }
            foreach($user->detections as $check){
                if($check instanceof Detection && $check->enabled){
                    $start = microtime(true);
                    $check->handle($packet, $user);
                    $time = microtime(true) - $start;
                    if($time > 0.01){
                        Mockingbird::getInstance()->debugTask->addData(get_class($check) . " took too long to process: $time");
                    }
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
            foreach($user->processors as $processor){
                $processor->processEvent($event);
            }
            $user->player->dataPacket($user->networkStackLatencyPacket);
            $user->lastSentNetworkLatencyTime = microtime(true);
        }
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $user = UserManager::getInstance()->get($entity);
            foreach($user->processors as $processor){
                $processor->processEvent($event);
            }
            foreach($user->detections as $detection){
                $detection->handleEvent($event, $user);
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
            foreach($user->processors as $processor){
                if($processor instanceof Processor){
                    $processor->processEvent($event);
                }
            }
            foreach($user->detections as $check){
                if($check instanceof Detection){
                    $check->handleEvent($event, $user);
                }
            }
        }
    }

    // I hate it here
    public function onTransaction(InventoryTransactionEvent $event) : void{
        $user = UserManager::getInstance()->get($event->getTransaction()->getSource());
        foreach($user->processors as $processor){
            $processor->processEvent($event);
        }
        foreach($user->detections as $detection){
            $detection->handleEvent($event, $user);
        }
    }

}