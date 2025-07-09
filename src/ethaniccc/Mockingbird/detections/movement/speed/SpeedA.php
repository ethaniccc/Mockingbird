<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class SpeedA
 * @package ethaniccc\Mockingbird\detections\movement\speed
 * SpeedA is a friction check which checks if the user's movement follows
 * Minecraft's friction rules. This catches some Jetpacks, and Bhops.
 */
class SpeedA extends NopDetection implements CancellableMovement{

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			if($user->moveData->offGroundTicks > 3){
				$lastMoveDelta = $user->moveData->lastMoveDelta;
				$currentMoveDelta = $user->moveData->moveDelta;
				$lastXZ = MathUtils::hypot($lastMoveDelta->x, $lastMoveDelta->z);
				$currentXZ = MathUtils::hypot($currentMoveDelta->x, $currentMoveDelta->z);
				$expectedXZ = $lastXZ * 0.91 + ($user->isSprinting ? 0.026 : 0.02);
				$equalness = $currentXZ - $expectedXZ;
				if($equalness > $this->getSetting('max_breach')
					&& $user->timeSinceStoppedFlight >= 20
					&& $user->timeSinceTeleport >= 2
					&& $user->timeSinceMotion >= 10 && !$user->player->isSpectator() && $user->timeSinceStoppedGlide >= 10
					&& $user->moveData->ticksSinceInVoid >= 10
					&& $user->hasReceivedChunks){
					$canFlag = true;
					// TODO: Depth strider is not implemented within PocketMine-MP.
					// foreach($user->player->getArmorInventory()->getContents() as $item){
					// 	if($item->hasEnchantment(VanillaEnchantments::DEPTH_STRIDER()) && $user->moveData->liquidTicks < 10){
					// 		$canFlag = false;
					// 		break;
					// 	}
					// }
					if($canFlag && ++$this->preVL >= 3){
						$this->fail($user, "e=$equalness cXZ=$currentXZ lXZ=$lastXZ");
					}
				}else{
					if($user->hasReceivedChunks){
						$this->preVL = 0;
						$this->reward($user, 0.01);
					}
				}
				if($this->isDebug($user)){
					$user->sendMessage("diff=$equalness curr=$currentXZ last=$lastXZ");
				}
			}
		}
	}
}