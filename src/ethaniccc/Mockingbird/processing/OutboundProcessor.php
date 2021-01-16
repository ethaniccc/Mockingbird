<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;

class OutboundProcessor extends Processor{

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $pk): void{
        $user = $this->user;
        // is it me... or does the server only send batch packets..?
        if($pk instanceof BatchPacket){
            foreach($pk->getPackets() as $buff){
                $packet = PacketPool::getPacket($buff);
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
                        $user->moveData->lastMotion = $packet->motion;
                        $user->timeSinceMotion -= $user->timeSinceMotion > 0 ? $user->timeSinceMotion : 3;
                        break;
                    case DisconnectPacket::NETWORK_ID:
                        $user->loggedIn = false;
                        UserManager::getInstance()->unregister($user->player);
                        break;
                }
                try{
                    $packet->decode();
                } catch(\RuntimeException $e){/* the packet could not be decoded */}
                // $user->testProcessor->process($packet);
                foreach($user->detections as $detection){
                    if($detection->enabled){
                        $detection->handleSend($packet, $user);
                    }
                }
            }
        }
    }

}