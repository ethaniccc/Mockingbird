<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
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
	private int $entities = 0;
	private ?Entity $lastEntity;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof InventoryTransactionPacket &&
			$packet->trData instanceof UseItemOnEntityTransactionData){
			$trData = $packet->trData;
			if($trData->getActionType() !== UseItemOnEntityTransactionData::ACTION_ATTACK){
				return;
			}
			$ent = $user->player->getWorld()->getEntity($trData->getActorRuntimeId());
			if($ent !== null && $this->lastEntity !== null && $ent->getId() !== $this->lastEntity->getId() && $ent->getPosition()->distance($this->lastEntity->getPosition()) > 2){
				++$this->entities;
				if($this->entities > 1){
					$this->fail($user, "entities={$this->entities}");
				}else{
					$this->reward($user, 0.075);
				}
			}
			$this->lastEntity = $ent;
			if($this->isDebug($user)){
				$user->sendMessage("entities={$this->entities}");
			}
		}elseif($packet instanceof PlayerAuthInputPacket){
			$this->entities = 0;
		}
	}
}