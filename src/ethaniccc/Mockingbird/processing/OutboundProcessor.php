<?php

namespace ethaniccc\Mockingbird\processing;

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

    public $pendingMotions = [];
    public $pendingLocations = [];
    public $pendingTeleports = [];

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
                                $user->player->dataPacket($user->chunkResponsePacket);
                            } else {
                                // even though this is a bad idea - assume the player received the chunks.
                                $user->hasReceivedChunks = true;
                            }
                            break;
                        case SetActorMotionPacket::NETWORK_ID:
                            /** @var SetActorMotionPacket $pk */
                            if($pk->entityRuntimeId === $user->player->getId()){
                                $pK = new NetworkStackLatencyPacket();
                                $pK->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
                                $pK->needResponse = true;
                                $user->player->dataPacket($pK);
                                $this->pendingMotions[$timestamp] = $pk->motion;
                                if($user->debugChannel === 'get-motion'){
                                    $user->sendMessage('sent ' . $timestamp . ' with motion ' . $pk->motion);
                                }
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
                                $pK = new NetworkStackLatencyPacket();
                                $pK->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
                                $pK->needResponse = true;
                                $user->player->dataPacket($pK);
                                $this->pendingLocations[$timestamp] = $location;
                                if($user->debugChannel === 'get-location'){
                                    $user->sendMessage('sent ' . $timestamp . ' with position ' . $location);
                                }
                            } elseif($packet instanceof MovePlayerPacket && $pk->mode === MovePlayerPacket::MODE_TELEPORT && $user->player->getId() === $pk->entityRuntimeId){
                                $this->pendingTeleports[] = $pk->position->subtract(0, 1.62, 0);
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
                                        $pK = new NetworkStackLatencyPacket();
                                        $pK->timestamp = mt_rand(10, 10000000) * 1000;
                                        $pK->needResponse = true;
                                        $user->player->dataPacket($pK);
                                        $user->ghostBlocks[$pK->timestamp] = $block;
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