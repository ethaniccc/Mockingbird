<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\User;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\DataPacket;

class EventProcessor extends Processor{

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        return;
    }

    public function processEvent(Event $event) : void{
        $user = $this->user;
        switch(get_class($event)){
            case BlockPlaceEvent::class:
                $user->timeSinceLastBlockPlace = 0;
                break;
            case EntityMotionEvent::class:
                /** @var EntityMotionEvent $event */
                $user->timeSinceMotion -= $user->timeSinceMotion > 0 ? $user->timeSinceMotion : 3;
                $user->moveData->lastMotion = $event->getVector();
                break;
            case PlayerJoinEvent::class:
                $user->loggedIn = true;
                if($user->player->hasPermission("mockingbird.alerts") && Mockingbird::getInstance()->getConfig()->get("alerts_default")){
                    $user->alerts = true;
                }
                $user->player->dataPacket($user->networkStackLatencyPacket);
                $user->lastSentNetworkLatencyTime = microtime(true);
                $user->responded = false;
                break;
        }
    }

}