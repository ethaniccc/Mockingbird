<?php

namespace ethaniccc\Mockingbird\detections\movement\omnisprint;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class OmniSprintA extends NopDetection{
	private bool $allowed = false;
	private int $graceBuffer = 0;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			$speedLevel = 0;
			$speedEffect = $user->player->getEffects()->get(VanillaEffects::SPEED());
			if($speedEffect !== null){
				$speedLevel = min($speedEffect->getAmplifier() + 1, 3);
			}
			
			if($user->player->isSprinting() && in_array('W', $user->moveData->pressedKeys)){
				$this->allowed = true;
				$this->graceBuffer = 0;
			}elseif(!$user->player->isSprinting()){
				$this->allowed = false;
				$this->graceBuffer = 0;
			}
			
			if($speedLevel >= 2 && $user->player->isSprinting() && !$this->allowed){
				$keys = $user->moveData->pressedKeys;
				$hasW = in_array('W', $keys);
				$hasLateral = in_array('A', $keys) || in_array('D', $keys);
				$hasS = in_array('S', $keys);
				
				if($hasW && $hasLateral && !$hasS){
					$this->graceBuffer = max(0, $this->graceBuffer - 1);
					if($this->graceBuffer <= 0){
						$this->allowed = true;
					}
				}
				elseif($hasS || (!$hasW && count($keys) > 0)){
					$this->graceBuffer = min($this->graceBuffer + 2, 10);
				}
			}
			
			$requiredBuffer = match($speedLevel) {
				0, 1 => 0,
				2 => 3,
				3 => 5,
				default => 0
			};
			
			if(!$this->allowed && count($user->moveData->pressedKeys) > 0 && $user->player->isSprinting()){
				if($speedLevel < 2 || $this->graceBuffer >= $requiredBuffer){
					$isSuspicious = true;
					if($speedLevel >= 2){
						$keys = $user->moveData->pressedKeys;
						$isSuspicious = in_array('S', $keys) || !in_array('W', $keys);
					}
					
					if($isSuspicious){
						$this->fail($user, 
							'keys=' . implode(',', $user->moveData->pressedKeys) . ' speed=' . $speedLevel . ' buffer=' . $this->graceBuffer,
							'keys=' . implode(',', $user->moveData->pressedKeys) . ' speed=' . $speedLevel
						);
					}
				}
			}
			
			if($this->isDebug($user)){
				$user->sendMessage('sprint=' . ($user->player->isSprinting() ? 'true' : 'false') . 
					' keys=' . implode(',', $user->moveData->pressedKeys) . 
					' speed=' . $speedLevel . 
					' buffer=' . $this->graceBuffer . 
					' allowed=' . ($this->allowed ? 'true' : 'false'));
			}
		}
	}
}