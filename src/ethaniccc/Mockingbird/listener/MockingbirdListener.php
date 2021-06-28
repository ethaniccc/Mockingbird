<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\processing\Processor;
use ethaniccc\Mockingbird\tasks\PacketLogWriteTask;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\block\UnknownBlock;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\PlayerMovementType;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use pocketmine\Player;
use pocketmine\Server;

class MockingbirdListener implements Listener{

    public function __construct(){
        Server::getInstance()->getPluginManager()->registerEvents($this, Mockingbird::getInstance());
    }

    /** @priority HIGHEST */
    public function onPacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if($packet instanceof LoginPacket){
            $user = new User($player);
            UserManager::getInstance()->register($user);
        } elseif($packet instanceof PlayerAuthInputPacket){
            $event->setCancelled();
        }

        $user = UserManager::getInstance()->get($player);
        if($user !== null){
            if($user->debugChannel === 'clientpk' && !in_array(get_class($packet), [BatchPacket::class, PlayerAuthInputPacket::class, NetworkStackLatencyPacket::class])){
                $user->sendMessage(get_class($packet));
            }
            if($user->isPacketLogged){
                $user->packetLog[] = $packet;
            }
            $user->inboundProcessor->process($packet, $user);
            foreach($user->detections as $check){
                if($check->enabled){
                    $check->handleReceive($packet, $user);
                }
            }
        }
    }

    /** @priority HIGHEST */
    public function onPacketSend(DataPacketSendEvent $event) : void{
        $packet = $event->getPacket();
        $user = UserManager::getInstance()->get($event->getPlayer());
        if($packet instanceof StartGamePacket){
        	$packet->playerMovementSettings = new PlayerMovementSettings(PlayerMovementType::SERVER_AUTHORITATIVE_V2_REWIND, 20, false);
        }
        if($packet instanceof BatchPacket){
            try{
                foreach($packet->getPackets() as $buff){
                    $pk = PacketPool::getPacket($buff);
                    $pk->decode();
                    // this is to prevent a glitch with Shulker boxes staying open and falsing movement checks
                    // if you have a plugin that properly implements Shulker boxes, then you should be fine.
                    if($pk instanceof ContainerOpenPacket && $user->player->getLevel()->getBlockAt($pk->x, $pk->y, $pk->z, false) instanceof UnknownBlock){
                        $event->setCancelled();
                    }
                }
            } catch(\UnexpectedValueException $e){}
        }
        if($user !== null){
            $user->outboundProcessor->process($packet, $user);
        }
    }

    // I hate it here
    public function onTransaction(InventoryTransactionEvent $event) : void{
        $user = UserManager::getInstance()->get($event->getTransaction()->getSource());
        if($user !== null){
            foreach($user->detections as $detection){
                if($detection->enabled){
                    $detection->handleEvent($event, $user);
                }
            }
        }
    }

    public function onLeave(PlayerQuitEvent $event) : void{
        $player = $event->getPlayer();
        UserManager::getInstance()->unregister($player);
    }

}