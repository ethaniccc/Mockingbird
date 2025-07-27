<?php

namespace ethaniccc\Mockingbird\detections\movement\velocity;

use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class VelocityA
 * @package ethaniccc\Mockingbird\detections\movement\velocity
 * VelocityA checks if the user's vertical velocity is lower than normal. This detection uses
 * NetworkStackLatency to confirm the client has received the SetActorMotion packet and then waits for
 * the next movement packet.
 */
class VelocityA extends NopDetection implements CancellableMovement{
	private Vector3 $receivedMotion;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
		$this->suppression = false;
		$this->vlSecondCount = 20;
		$this->lowMax = 4;
		$this->mediumMax = 8;
		$this->receivedMotion = new Vector3(0, 0, 0);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			if($user->timeSinceMotion <= 1){
				$this->receivedMotion = $user->moveData->lastMotion;
			}
			if($this->receivedMotion->y > 0.005){
				$currentYDelta = $user->moveData->moveDelta->y;
				$percentage = ($currentYDelta / $this->receivedMotion->y) * 100;
				// against walls this check for some reason will false at ~99.9999%, what the fuck
				$collisionAABB = clone $user->moveData->AABB;
				$collisionAABB->minY = $collisionAABB->maxY;
				$collisionAABB->maxY += 0.2;
				$collisionAABB->grow(-0.2, 0.2, -0.2);
				$pmmpAABB = AABB::toPMMPAABB($collisionAABB);
				if($percentage < 99.99 && count($user->player->getWorld()->getCollisionBlocks($pmmpAABB, true)) === 0 && $user->moveData->liquidTicks >= 10 && $user->moveData->cobwebTicks >= 10
					&& $user->moveData->levitationTicks >= 10 && $user->timeSinceTeleport >= 10 && $user->timeSinceStoppedFlight >= 10 && $user->timeSinceStoppedGlide >= 10){
					if(++$this->preVL >= 5){
						$roundedPercentage = round($percentage, 3);
						$roundedBuffer = round($this->preVL, 2);
						$this->fail($user, "(A) percentage=$percentage% buff={$this->preVL}", "pct=$roundedPercentage% buff=$roundedBuffer");
						$this->preVL = min($this->preVL, 30);
					}
				}else{
					$this->reward($user, $user->transactionLatency > 400 ? 0.4 : 0.2);
					$this->preVL = max($this->preVL - 0.5, 0);
				}
				$this->receivedMotion->y = ($this->receivedMotion->y - 0.08) * 0.980000012;
				if($this->isDebug($user)){
					$user->sendMessage("percentage=$percentage% latency={$user->transactionLatency} buff={$this->preVL}");
				}
			}
		}
	}
}