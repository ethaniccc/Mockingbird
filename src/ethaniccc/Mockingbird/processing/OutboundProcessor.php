<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\handler\NetworkStackLatencyHandler;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\block\BlockIds;
use pocketmine\block\Transparent;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\block\Block;

class OutboundProcessor extends Processor{

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