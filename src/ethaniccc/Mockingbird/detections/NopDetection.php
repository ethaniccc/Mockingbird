<?php

namespace ethaniccc\Mockingbird\detections;

use ethaniccc\Mockingbird\user\User;
use pocketmine\event\Event;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class NopDetection extends Detection{

	public function handleReceive(DataPacket $packet, User $user) : void{
		// NOOP.
	}

	public function handleSend(DataPacket $packet, User $user) : void{
		// NOOP.
	}

	public function handleEvent(Event $event, User $user) : void{
		// NOOP.
	}
}