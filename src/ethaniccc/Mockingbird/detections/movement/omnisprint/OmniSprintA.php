<?php

namespace ethaniccc\Mockingbird\detections\movement\omnisprint;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class OmniSprintA extends NopDetection{
	private bool $allowed = false;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			// Microjang is high and allows players to sprint backwards in this scenario.
			if($user->player->isSprinting() && in_array('W', $user->moveData->pressedKeys)){
				$this->allowed = true;
			}elseif(!$user->player->isSprinting()){
				$this->allowed = false;
			}
			if(!$this->allowed && count($user->moveData->pressedKeys) > 0 && $user->player->isSprinting()){
				$this->fail($user, 'keys=' . implode(',', $user->moveData->pressedKeys));
			}
			if($this->isDebug($user)){
				$user->sendMessage('sprint=' . ($user->player->isSprinting() ? 'true' : 'false') . ' keys=' . implode(',', $user->moveData->pressedKeys));
			}
		}
	}
}