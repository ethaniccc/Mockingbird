<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\packets\MotionPacket;
use ethaniccc\Mockingbird\processing\Processor;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
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
            foreach($user->checks as $check){
                if($check instanceof Detection){
                    $check->handle($packet, $user);
                }
            }
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
        }
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $user = UserManager::getInstance()->get($entity);
            $user->timeSinceMotion = 0;
            $user->currentMotion = $event->getVector();
            $motionPK = new MotionPacket($event);
            foreach($user->checks as $check){
                if($check instanceof Detection){
                    $check->handle($motionPK, $user);
                }
            }
        }
    }

}