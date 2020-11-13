<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;

class OtherPacketProcessor extends Processor{

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        $user = $this->user;
        if($packet instanceof LoginPacket){
            $user->isDesktop = !in_array($packet->clientData["DeviceOS"], [DeviceOS::AMAZON, DeviceOS::ANDROID, DeviceOS::IOS]);
        } elseif($packet instanceof NetworkStackLatencyPacket){
            if($packet->timestamp === $user->networkStackLatencyPacket->timestamp){
                $user->transactionLatency = round((microtime(true) - $user->lastSentNetworkLatencyTime) * 1000, 0);
                $user->player->dataPacket($user->networkStackLatencyPacket);
                $user->lastSentNetworkLatencyTime = microtime(true);
            }
        }
    }

}