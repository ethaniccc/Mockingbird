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
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\entity\Villager;
use pocketmine\Server;

class TickProcessor extends RunnableProcessor{

    /** @var int - The current tick the client is currently on. */
    private $ticks = 0;
    /** @var int - The previous tick of the user. */
    private $lastTick = 0;
    /** @var int - The amount of ticks the user current tick is the same. */
    private $stuckTicks = 0;
    /** @var int - The amount of ticks the client has not responded to the NetworkStackLatency packet */
    private $noResponseTicks = 0;

    public function run(User $user) : void{
        if(!$user->loggedIn)
            return;
        if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1 && $user->responded){
            $user->player->dataPacket($user->latencyPacket);
            $user->lastSentNetworkLatencyTime = microtime(true);
            $user->responded = false;
        }
        $this->stuckTicks = $this->ticks === $this->lastTick ? $this->stuckTicks + 1 : 0;
        if($this->ticks !== $this->lastTick && !$user->responded && ++$this->noResponseTicks >= 100){
            Mockingbird::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($user, 'NetworkStackLatency timeout (bad connection?) - Rejoin server'), 1);
        } elseif($user->responded) {
            $this->noResponseTicks = 0;
        }
        if($user->debugChannel === 'tick'){
            $user->sendMessage('client=' . $this->ticks . ' server=' . Server::getInstance()->getTick());
        }
        $this->lastTick = $this->ticks;
    }

    public function process(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket){
            $user->tickData->currentTick = ++$this->ticks;
        }
    }

}