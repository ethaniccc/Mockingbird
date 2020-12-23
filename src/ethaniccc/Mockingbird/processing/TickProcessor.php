<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\utils\location\LocationHistory;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class TickProcessor extends RunnableProcessor{

    /** @var int - The current tick the client is currently on. */
    private $ticks = 0;
    /** @var int - The previous client tick. */
    private $lastTick = 0;
    /** @var null|Entity */
    private $lastTarget;
    /** @var int - The amount of ticks the client's tick is the same. */
    private $sameTick = 0;

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
                // get rid of the old location history because obsolete
                unset($this->user->tickData->targetLocationHistory);
                $this->user->tickData->targetLocationHistory = new LocationHistory();
            }
            $add = true;
            if($this->ticks === $this->lastTick){
                // the client is lagging and hasn't gotten the updated position of the target entity.
                if(++$this->sameTick >= 20) $add = false;
            } else {
                $this->sameTick = 0;
            }
            if($add) $this->user->tickData->targetLocationHistory->addLocation($targetEntity->asVector3(), $this->ticks);
        }
        $this->lastTarget = $targetEntity;
        if(microtime(true) - $this->user->lastSentNetworkLatencyTime >= 1 && $this->user->responded){
            $this->user->player->dataPacket($this->user->networkStackLatencyPacket);
            $this->user->lastSentNetworkLatencyTime = microtime(true);
            $this->user->responded = false;
        }
        $this->lastTick = $this->ticks;
    }

    public function process(DataPacket $packet) : void{
        if($packet instanceof PlayerAuthInputPacket){
            if(ProtocolInfo::CURRENT_PROTOCOL <= 408){
                $this->ticks = $this->user->tickData->currentTick = ($this->ticks + 1);
            } else {
                $this->ticks = $this->user->tickData->currentTick = $packet->getTick();
            }
        }
    }

}