<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

/**
 * Class KillAuraA
 * @package ethaniccc\Mockingbird\detections\combat\killaura
 * KillAuraA checks if the user is hitting too many entities in the same tick.
 */
class KillAuraA extends NopDetection{
	private array $entities = [];

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if ($packet instanceof InventoryTransactionPacket) {
			$trData = $packet->trData;
			if ($trData instanceof UseItemOnEntityTransactionData && $trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK) {
				if (!in_array($trData->getActorRuntimeId(), $this->entities, true)) {
					$this->entities[] = $trData->getActorRuntimeId();
				}
			}
		} elseif ($packet instanceof PlayerAuthInputPacket) {
			if (count($this->entities) > 1) {
				$lastAABB = null;
				$collides = false;
				foreach ($this->entities as $ignored) {
					$lastLocation = $user->moveData->lastLocation;
					$AABB = AABB::toPMMPAABB(AABB::fromPosition($lastLocation))->expandedCopy(0.3, 0.3, 0.3);
					if ($lastAABB !== null) {
						$collides = $AABB->intersectsWith($lastAABB);
						if ($collides) {
							break;
						}
					}
					$lastAABB = $AABB;
				}
				if (!$collides) {
					$this->fail($user, "entities={$this->entities}");
				}
			}
			if($this->isDebug($user)){
				$user->sendMessage("entities={$this->entities}");
			}
			$this->entities = [];
		}
	}
}