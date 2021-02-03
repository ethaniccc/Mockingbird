<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;

class OutboundProcessor extends Processor{

    public $pendingMotions = [];
    public $pendingLocations = [];

    public function process(DataPacket $pk, User $user): void{
        // is it me... or does the server only send batch packets..?
        if($pk instanceof BatchPacket){
            try{
                foreach($pk->getPackets() as $buff){
                    $packet = PacketPool::getPacket($buff);
                    try{
                        $packet->decode();
                    } catch(\RuntimeException $e){/* the packet could not be decoded */}
                    switch($packet->pid()){
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
                            /** @var SetActorMotionPacket $packet */
                            if($packet->entityRuntimeId === $user->player->getId()){
                                $pK = new NetworkStackLatencyPacket();
                                $pK->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
                                $pK->needResponse = true;
                                $user->player->dataPacket($pK);
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
                            if($user->hitData->targetEntity !== null && $packet->entityRuntimeId === $user->hitData->targetEntity->getId()){
                                $location = $packet->pid() === MovePlayerPacket::NETWORK_ID ? $packet->position->subtract(0, 1.62, 0) : $packet->position;
                                $pK = new NetworkStackLatencyPacket();
                                $pK->timestamp = ($timestamp = mt_rand(10, 10000000) * 1000);
                                $pK->needResponse = true;
                                $user->player->dataPacket($pK);
                                $this->pendingLocations[$timestamp] = $location;
                                if($user->debugChannel === 'get-location'){
                                    $user->sendMessage('sent ' . $timestamp . ' with position ' . $location);
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
            } catch(\UnexpectedValueException $e){}
        }
    }

}