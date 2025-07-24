<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class OutboundProcessor extends Processor{

	public array $pendingMotions = [];
	public array $pendingLocations = [];
	public array $pendingTeleports = [];

	public function process(DataPacket $packet, User $user) : void{
		switch($packet->pid()){
			case NetworkChunkPublisherUpdatePacket::NETWORK_ID:
				if($user->loggedIn){
					$user->hasReceivedChunks = false;
					$user->player->getNetworkSession()->sendDataPacket($user->chunkResponsePacket);
				}else{
					// even though this is a bad idea - assume the player received the chunks.
					$user->hasReceivedChunks = true;
				}
				break;
			case SetActorMotionPacket::NETWORK_ID:
				/** @var SetActorMotionPacket $packet */
				if($packet->actorRuntimeId === $user->player->getId()){
					$packet = new NetworkStackLatencyPacket();
					$packet->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
					$packet->needResponse = true;
					$user->player->getNetworkSession()->sendDataPacket($packet);
					$this->pendingMotions[$timestamp] = $packet->motion;
					if($user->debugChannel === 'get-motion'){
						$user->sendMessage('sent ' . $timestamp . ' with motion ' . $packet->motion);
					}
				}
				break;
			case DisconnectPacket::NETWORK_ID:
				$user->loggedIn = false;
				UserManager::getInstance()->unregister($user->player);
				break;
			case MovePlayerPacket::NETWORK_ID:
			case MoveActorAbsolutePacket::NETWORK_ID:
				/** @var MovePlayerPacket|MoveActorAbsolutePacket $packet */
				if($user->hitData->targetEntity !== null && $packet->actorRuntimeId === $user->hitData->targetEntity->getId()){
					$location = $packet->pid() === MovePlayerPacket::NETWORK_ID ? $packet->position->subtract(0, 1.62, 0) : $packet->position;
					$packet = new NetworkStackLatencyPacket();
					$packet->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
					$packet->needResponse = true;
					$user->player->getNetworkSession()->sendDataPacket($packet);
					$this->pendingLocations[$timestamp] = $location;
					if($user->debugChannel === 'get-location'){
						$user->sendMessage('sent ' . $timestamp . ' with position ' . $location);
					}
				}elseif($packet instanceof MovePlayerPacket && $packet->mode === MovePlayerPacket::MODE_TELEPORT && $user->player->getId() === $packet->actorRuntimeId){
					$this->pendingTeleports[] = $packet->position->subtract(0, 1.62, 0);
				}
				break;
			case UpdateBlockPacket::NETWORK_ID:
				/** @var UpdateBlockPacket $packet */
				$pos = new Vector3($packet->blockPosition->getX(), $packet->blockPosition->getY(), $packet->blockPosition->getZ());
				$found = false;
				foreach($user->placedBlocks as $block){
					$dist = $block->getPosition()->subtractVector($pos)->lengthSquared();
					if($dist === 0.0){
						$found = true;
						break;
					}
				}
				$blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
				// the block is going to be set to air, and it's position is one of the positions of the blocks the user placed..
				// if($found) $user->sendMessage('runtime=' . $packet->blockRuntimeId . ' id=' . RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId)[0] . ' meta=' . RuntimeBlockMapping::fromStaticRuntimeId($packet->blockRuntimeId)[1] . ' flags=' . $packet->flags . ' data=' . $packet->dataLayerId . ' pos=(' . $packet->x . ',' . $packet->y . ',' . $packet->z . ')');
				if($packet->blockRuntimeId === $blockTranslator->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId()) && $found){
					foreach($user->placedBlocks as $search => $block){
						if($block->getPosition()->subtractVector($pos)->lengthSquared() === 0.0){
							$packet = new NetworkStackLatencyPacket();
							$packet->timestamp = mt_rand(10, 10000000) * 1000;
							$packet->needResponse = true;
							$user->player->getNetworkSession()->sendDataPacket($packet);
							$user->ghostBlocks[$packet->timestamp] = $block;
							if($user->debugChannel === 'ghost-block'){
								$user->sendMessage('ghost block ' . $block->getTypeId() . ' client-side with (x=' . $block->getPosition()->getX() . ' y=' . $block->getPosition()->getY() . ' z=' . $block->getPosition()->getZ() . ')');
							}
							unset($user->placedBlocks[$search]);
						}
					}
				}elseif($packet->blockRuntimeId !== $blockTranslator->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId()) && $found){
					foreach($user->placedBlocks as $search => $block){
						if($block->getPosition()->subtractVector($pos)->lengthSquared() === 0.0){
							unset($user->placedBlocks[$search]);
						}
					}
				}
				break;
		}
		// $user->testProcessor->process($packet);
		foreach($user->detections as $detection){
			if($detection->enabled && $detection->canHandleSend()){
				$detection->handleSend($packet, $user);
			}
		}
	}
}