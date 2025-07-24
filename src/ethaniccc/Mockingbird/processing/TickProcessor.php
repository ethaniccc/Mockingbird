<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class TickProcessor extends NopProcessor{

	/** The current tick the client is currently on. */
	private int $ticks = 0;
	/** The previous tick of the user. */
	private int $lastTick = 0;
	/** The amount of ticks the user current tick is the same. */
	private int $stuckTicks = 0;
	/** The amount of ticks the client has not responded to the NetworkStackLatency packet */
	private int $noResponseTicks = 0;

	public function run(User $user) : void{
		if(!$user->loggedIn)
			return;
		if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1 && $user->responded){
			$user->player->getNetworkSession()->sendDataPacket($user->latencyPacket);
			$user->lastSentNetworkLatencyTime = microtime(true);
			$user->responded = false;
		}
		$this->stuckTicks = $this->ticks === $this->lastTick ? $this->stuckTicks + 1 : 0;
		if($this->ticks !== $this->lastTick && !$user->responded && ++$this->noResponseTicks >= 100){
			Mockingbird::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($user, 'NetworkStackLatency timeout (bad connection?) - Rejoin server'), 1);
		}elseif($user->responded){
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