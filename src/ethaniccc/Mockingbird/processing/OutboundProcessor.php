<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\handler\NetworkStackLatencyHandler;
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

<<<<<<< HEAD
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
					$nsl = new NetworkStackLatencyPacket();
					$nsl->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
					$nsl->needResponse = true;
					$user->player->getNetworkSession()->sendDataPacket($nsl);
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
					$nsl = new NetworkStackLatencyPacket();
					$nsl->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
					$nsl->needResponse = true;
					$user->player->getNetworkSession()->sendDataPacket($nsl);
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
							$nsl = new NetworkStackLatencyPacket();
							$nsl->timestamp = mt_rand(10, 10000000) * 1000;
							$nsl->needResponse = true;
							$user->player->getNetworkSession()->sendDataPacket($nsl);
							$user->ghostBlocks[$nsl->timestamp] = $block;
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
=======
    public function process(DataPacket $packet, User $user): void{
        // is it me... or does the server only send batch packets..?
        if($packet instanceof BatchPacket){
            try{
                foreach($packet->getPackets() as $buff){
                    $pk = PacketPool::getPacket($buff);
                    try{
                        $pk->decode();
                    } catch(\RuntimeException $e){/* the packet could not be decoded */}
                    switch($pk->pid()){
                        case NetworkChunkPublisherUpdatePacket::NETWORK_ID:
                            if($user->loggedIn){
                                $user->hasReceivedChunks = false;
                                NetworkStackLatencyHandler::send($user, NetworkStackLatencyHandler::random(), function(int $currentTick) use ($user) : void{
                                    $user->hasReceivedChunks = true;
                                    if($user->debugChannel === 'receive-chunk'){
                                        $user->sendMessage('received chunks');
                                    }
                                });
                            } else {
                                // even though this is a bad idea - assume the player received the chunks.
                                $user->hasReceivedChunks = true;
                            }
                            break;
                        case SetActorMotionPacket::NETWORK_ID:
                            /** @var SetActorMotionPacket $pk */
                            if($pk->entityRuntimeId === $user->player->getId()){
                                $motion = $pk->motion;
                                NetworkStackLatencyHandler::send($user, NetworkStackLatencyHandler::random(), function(int $timestamp) use($motion, $user) : void{
                                    $user->moveData->lastMotion = $motion;
                                    $user->timeSinceMotion = 0;
                                    $user->tickProcessor->noResponseTicks = 0;
                                });
                            }
                            break;
                        case DisconnectPacket::NETWORK_ID:
                            $user->loggedIn = false;
                            UserManager::getInstance()->unregister($user->player);
                            break;
                        case MovePlayerPacket::NETWORK_ID:
                        case MoveActorAbsolutePacket::NETWORK_ID:
                            /** @var MovePlayerPacket|MoveActorAbsolutePacket $pk */
                            if($user->hitData->targetEntity !== null && $pk->entityRuntimeId === $user->hitData->targetEntity->getId()){
                                $location = $pk->pid() === MovePlayerPacket::NETWORK_ID ? $pk->position->subtract(0, 1.62, 0) : $pk->position;
                                NetworkStackLatencyHandler::send($user, NetworkStackLatencyHandler::random(), function(int $timestamp) use($user, $location) : void{
                                    $user->tickData->targetLocations[$user->tickData->currentTick] = $location;
                                    $currentTick = $user->tickData->currentTick;
                                    $user->tickData->targetLocations = array_filter($user->tickData->targetLocations, function(int $tick) use($currentTick) : bool{
                                        return $currentTick - $tick <= 4;
                                    }, ARRAY_FILTER_USE_KEY);
                                    $user->tickProcessor->noResponseTicks = 0;
                                });
                            } elseif($pk instanceof MovePlayerPacket && $user->player->getId() === $pk->entityRuntimeId){
                                if($pk->mode === MovePlayerPacket::MODE_RESET || $pk->mode === MovePlayerPacket::MODE_TELEPORT){
                                    $user->moveData->forceMoveSync = $pk->position->subtract(0, 1.62, 0);
                                }
                            }
                            break;
                        case UpdateBlockPacket::NETWORK_ID:
                            /** @var UpdateBlockPacket $pk */
                            $pos = new Vector3($pk->x, $pk->y, $pk->z);
                            $found = false;
                            foreach($user->placedBlocks as $block){
                                $dist = $block->asVector3()->subtract($pos)->lengthSquared();
                                if($dist === 0.0){
                                    $found = true;
                                    break;
                                }
                            }
                            // the block is going to be set to air, and it's position is one of the positions of the blocks the user placed..
                            // if($found) $user->sendMessage('runtime=' . $pk->blockRuntimeId . ' id=' . RuntimeBlockMapping::fromStaticRuntimeId($pk->blockRuntimeId)[0] . ' meta=' . RuntimeBlockMapping::fromStaticRuntimeId($pk->blockRuntimeId)[1] . ' flags=' . $pk->flags . ' data=' . $pk->dataLayerId . ' pos=(' . $pk->x . ',' . $pk->y . ',' . $pk->z . ')');
                            if($pk->blockRuntimeId === 134 && $found){
                                foreach($user->placedBlocks as $search => $block){
                                    if($block->asVector3()->subtract($pos)->lengthSquared() === 0.0){
                                        $pK = NetworkStackLatencyHandler::random();
                                        $user->ghostBlocks[$pK->timestamp] = $block;
                                        NetworkStackLatencyHandler::send($user, $pK, function(int $timestamp) use($block, $user) : void{
                                            if($user->debugChannel === 'ghost-block'){
                                                $user->sendMessage('ghost block ' . $block->getId() . ' removed with (x=' . $block->getX() . ' y=' . $block->getY() . ' z=' . $block->getZ() . ')');
                                            }
                                            unset($user->ghostBlocks[$timestamp]);
                                            $user->tickProcessor->noResponseTicks = 0;
                                        });
                                        if($user->debugChannel === 'ghost-block'){
                                            $user->sendMessage('ghost block ' . $block->getId() . ' client-side with (x=' . $block->getX() . ' y=' . $block->getY() . ' z=' . $block->getZ() . ')');
                                        }
                                        unset($user->placedBlocks[$search]);
                                    }
                                }
                            } elseif($pk->blockRuntimeId !== 134 && $found){
                                foreach($user->placedBlocks as $search => $block){
                                    if($block->asVector3()->subtract($pos)->lengthSquared() === 0.0){
                                        unset($user->placedBlocks[$search]);
                                    }
                                }
                            }
                            break;
                    }
                    // $user->testProcessor->process($packet);
                    foreach($user->detections as $detection){
                        if($detection->enabled && $detection->canHandleSend()){
                            $detection->handleSend($pk, $user);
                        }
                    }
                }
            } catch(\UnexpectedValueException $e){}
        } else {
            try{
                $packet->decode();
            } catch(\RuntimeException $e){}

        }
    }

}
>>>>>>> 65e40d1669fcf4de3afd3d52050ca3cc552fad65
