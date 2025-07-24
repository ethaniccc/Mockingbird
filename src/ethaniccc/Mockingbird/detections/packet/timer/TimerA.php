<?php

namespace ethaniccc\Mockingbird\detections\packet\timer;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class TimerA
 * @package ethaniccc\Mockingbird\detections\packet\timer
 * TimerA checks if a player is sending movement packets too fast while accounting for lag (this will no longer false on server lag).
 * The way TimerA accounts for lag is by using a concept of "balance". These movement packets should be sending 1 tick every time, so
 * every time we receive a movement packet, we get the time difference in ticks, and add that to the balance. From there, we subtract by
 * one, as one is the expected time it should be taking. If the balance goes below a threshold (-5), flag.
 */
class TimerA extends NopDetection{
	private ?float $lastTime;
	private float $balance = 0;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
		$this->lowMax = 0;
		$this->mediumMax = 0;
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			if($user->timeSinceJoin < 20 || !$user->player->isAlive()){
				$this->balance = 0;
				$this->lastTime = null;
				return;
			}
			$currentTime = microtime(true) * 1000;
			if ($this->lastTime === null) {
				$this->lastTime = $currentTime;
				return;
			}

			$timeDiff = round(($currentTime - $this->lastTime) / 50, 2);

			$this->balance--;
			$this->balance += $timeDiff;

			if ($this->balance <= -3) {
				$this->fail($user);
				$this->balance = 0;
			}

			if ($this->balance >= 200) {
				$this->balance = 0;
			}

			$this->lastTime = $currentTime;
			if($this->isDebug($user)){
				$user->sendMessage("balance={$this->balance}");
			}
		}
	}
}