<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use ethaniccc\Mockingbird\utils\Pair;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\entity\Villager;

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

    public function run() : void{
        if(!$this->user->loggedIn){
            return;
        }
        if($this->user->tickData->targetLocationHistory === null){
            $this->user->tickData->targetLocationHistory = new LocationHistory();
        }
        $targetEntity = $this->user->hitData->targetEntity;
        if($targetEntity !== null && $this->lastTarget !== null){
            if($targetEntity->getId() !== $this->lastTarget->getId()){
                // remove all locations
                $this->user->tickData->targetLocationHistory->clearLocations();
            }
            // if the user is not frozen
            if($this->stuckTicks <= 10){
                $this->user->tickData->targetLocationHistory->addLocation(AABB::fromAxisAlignedBB($targetEntity->getBoundingBox()), $this->ticks);
            }
        }
        $this->lastTarget = $targetEntity;
        if(microtime(true) - $this->user->lastSentNetworkLatencyTime >= 1 && $this->user->responded){
            $this->user->player->dataPacket($this->user->latencyPacket);
            $this->user->lastSentNetworkLatencyTime = microtime(true);
            $this->user->responded = false;
        }
        $this->stuckTicks = $this->ticks === $this->lastTick ? $this->stuckTicks + 1 : 0;
        $this->lastTick = $this->ticks;
    }

    public function process(DataPacket $packet) : void{
        if($packet instanceof PlayerAuthInputPacket){
            if(ProtocolInfo::CURRENT_PROTOCOL <= 408){
                $this->ticks = $this->user->tickData->currentTick = ($this->ticks + 1);
            } else {
                $this->ticks = $this->user->tickData->currentTick = $packet->getTick();
            }
            /*
            if(!$this->user->responded){
                ++$this->noResponseTicks;
                // no disablers 4u :)
                if($this->noResponseTicks >= 150){
                    Mockingbird::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($this->user, 'NetworkStackLatency timeout (bad connection?) - rejoin server.'), 0);
                }
                if($this->noResponseTicks % 50 === 0){
                    $this->user->player->dataPacket($this->user->latencyPacket);
                }
            } else {
                $this->noResponseTicks = 0;
            }
            */
        }
    }

}