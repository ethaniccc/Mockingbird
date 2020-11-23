<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\User;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\DataPacket;
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
            try{
                $data = $packet->chainData;
                $parts = explode(".", $data['chain'][2]);
                $jwt = json_decode(base64_decode($parts[1]), true);
                $id = $jwt['extraData']['titleId'];
                $user->win10 = ($id === "896928775");
            } catch(\Exception $e){
            }
        } elseif($packet instanceof NetworkStackLatencyPacket){
            if($packet->timestamp === $user->networkStackLatencyPacket->timestamp){
                $user->transactionLatency = round((microtime(true) - $user->lastSentNetworkLatencyTime) * 1000, 0);
                $user->player->dataPacket($user->networkStackLatencyPacket);
                $user->lastSentNetworkLatencyTime = microtime(true);
            }
        }
    }

    public function processEvent(Event $event): void{
        $user = $this->user;
        if($event instanceof PlayerJoinEvent){
            $user->loggedIn = true;
            if($user->player->hasPermission("mockingbird.alerts") && Mockingbird::getInstance()->getConfig()->get("alerts_default")){
                $user->alerts = true;
            }
        } elseif($event instanceof BlockPlaceEvent){
            $user->timeSinceLastBlockPlace = 0;
        } elseif($event instanceof EntityMotionEvent){
            $user->timeSinceMotion -= $user->timeSinceMotion > 0 ? $user->timeSinceMotion : 3;
            $user->moveData->lastMotion = $event->getVector();
        }
    }

}