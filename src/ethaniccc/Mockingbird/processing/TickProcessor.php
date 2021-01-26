<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use ethaniccc\Mockingbird\utils\Pair;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\entity\Villager;
use pocketmine\Server;

class TickProcessor extends RunnableProcessor{

    /** @var int - The current tick the client is currently on. */
    private $ticks = 0;
    /** @var null|Entity */
    private $lastTarget;
    /** @var int - The previous tick of the user. */
    private $lastTick = 0;
    /** @var int - The amount of ticks the user current tick is the same. */
    private $stuckTicks = 0;
    /** @var int - The amount of ticks the client has not responded to the NetworkStackLatency packet */
    private $noResponseTicks = 0;

    public function run(User $user) : void{
        if(!$user->loggedIn){
            return;
        }
        if($user->tickData->targetLocationHistory === null){
            $user->tickData->targetLocationHistory = new LocationHistory();
        }
        $targetEntity = $user->hitData->targetEntity;
        if($targetEntity !== null && $this->lastTarget !== null){
            if($targetEntity->getId() !== $this->lastTarget->getId()){
                // remove all locations
                $user->tickData->targetLocationHistory->clearLocations();
            }
            // if the user is not frozen
            if($this->stuckTicks <= 10){
                $user->tickData->targetLocationHistory->addLocation(AABB::fromAxisAlignedBB($targetEntity->getBoundingBox()), $this->ticks);
            }
        }
        $this->lastTarget = $targetEntity;
        if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1 && $user->responded){
            $user->player->dataPacket($user->latencyPacket);
            $user->lastSentNetworkLatencyTime = microtime(true);
            $user->responded = false;
        }
        $this->stuckTicks = $this->ticks === $this->lastTick ? $this->stuckTicks + 1 : 0;
        if($this->ticks !== $this->lastTick && !$user->responded){
            if(++$this->noResponseTicks >= 30){
                Mockingbird::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($user, 'NetworkStackLatency timeout (bad connection?) - Rejoin server'), 1);
            }
        } else {
            $this->noResponseTicks = 0;
        }
        if($user->debugChannel === 'tick'){
            $user->sendMessage('client=' . $this->ticks . ' server=' . Server::getInstance()->getTick());
        }
        $this->lastTick = $this->ticks;
    }

    public function process(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket){
            if(ProtocolInfo::CURRENT_PROTOCOL >= 419){
                $user->tickData->currentTick = $this->ticks = $packet->getTick();
            } else {
                $user->tickData->currentTick = $this->ticks++;
            }
        }
    }

}