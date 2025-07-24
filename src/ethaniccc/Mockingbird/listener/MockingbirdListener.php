<?php

namespace ethaniccc\Mockingbird\listener;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\block\UnknownBlock;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class MockingbirdListener implements Listener{

	public function __construct(){
		Server::getInstance()->getPluginManager()->registerEvents($this, Mockingbird::getInstance());
	}

	/** @priority HIGHEST */
	public function onLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$user = new User($player);
		UserManager::getInstance()->register($user);
	}

	/** @priority HIGHEST */
	public function onPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getOrigin()->getPlayer();
		if($player === null){
			return;
		}

		$user = UserManager::getInstance()->get($player);
		if($user !== null){
			if($user->debugChannel === 'clientpk' && !in_array(get_class($packet), [PlayerAuthInputPacket::class, NetworkStackLatencyPacket::class])){
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
		$packet = $event->getPackets();
		foreach($event->getTargets() as $target){
			$player = $target->getPlayer();
			if($player === null){
				continue;
			}
			$user = UserManager::getInstance()->get($player);
			if($user === null){
				continue;
			}
			foreach($event->getPackets() as $pk){
				// this is to prevent a glitch with Shulker boxes staying open and falsing movement checks
				// if you have a plugin that properly implements Shulker boxes, then you should be fine.
				if($pk instanceof ContainerOpenPacket &&
					$user->player->getWorld()->getBlockAt(
						$pk->blockPosition->getX(), $pk->blockPosition->getY(), $pk->blockPosition->getZ(),
						false
					) instanceof UnknownBlock){
					$event->cancel();
				}
				$user->outboundProcessor->process($pk, $user);
			}
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