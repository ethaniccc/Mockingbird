<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class NopProcessor extends Processor{

	public function process(DataPacket $packet, User $user) : void{
		// NOOP.
	}

	abstract public function run(User $user) : void;
}