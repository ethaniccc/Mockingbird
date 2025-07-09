<?php

namespace ethaniccc\Mockingbird\tasks;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\scheduler\Task;

class KickTask extends Task{
	private User $user;
	private string $message;

	public function __construct(User $user, string $message){
		$this->user = $user;
		$this->message = $message;
	}

	public function onRun() : void{
		$player = $this->user->player;
		$player->kick($this->message, false);
		UserManager::getInstance()->unregister($player);
	}
}