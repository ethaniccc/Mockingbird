<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

/**
 * Class BadPacketC
 * @package ethaniccc\Mockingbird\detections\packet\badpackets
 * BadPacketC checks if the user is hitting.. themselves. This type of BS
 * is used in some fly bypasses.
 */
class BadPacketC extends NopDetection{

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
			$targetEntity = $trData->getActorRuntimeId();
			if($user->player->getId() === $targetEntity){
				$this->fail($user, "id={$user->player->getId()} attackedId=$targetEntity");
			}
		}
	}
}