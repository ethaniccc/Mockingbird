<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\utils\TextFormat;

/**
 * Class ReachA
 * @package ethaniccc\Mockingbird\detections\combat\reach
 * ReachA uses locations the client has received of the entity and
 * creates bounding boxes from those locations. With those bounding boxes, we get the distance from the user's
 * current eye pos and last eye pos to the bounding box [@see AABB::distanceFromVector()] and store that in a list, then gets the minimum distance in the list.
 * If the distance exceeds a threshold and the buffer exceeds a level, flag.
 */

class ReachA extends NopDetection{
	private bool $awaitingMove = false;
	
	private const BASE_THRESHOLD = 3.075;
	private const SPEED_MULTIPLIERS = [
		0 => 1.0,
		1 => 1.05,
		2 => 1.12,
		3 => 1.20
	];

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
		$this->vlSecondCount = 20;
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof InventoryTransactionPacket && !$user->player->isCreative() && !$this->awaitingMove && $packet->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK && $user->hitData->targetEntity === $user->hitData->lastTargetEntity){
			if(count($user->tickData->targetLocations) >= 2){
				$this->awaitingMove = true;
			}
		}elseif($packet instanceof PlayerAuthInputPacket && $this->awaitingMove){
			$list = new EvictingList(PHP_INT_MAX);
			foreach($user->tickData->targetLocations as $location){
				$AABB = AABB::fromPosition($location);
				$list->add($AABB->distanceFromVector($user->hitData->attackPos));
				$list->add($AABB->distanceFromVector($packet->getPosition()));
				unset($AABB);
			}
			$distance = $list->minOrElse(-1.0);
			if($distance !== -1.0){
				$speedLevel = 0;
				$speedEffect = $user->player->getEffects()->get(VanillaEffects::SPEED());
				if($speedEffect !== null){
					$speedLevel = min($speedEffect->getAmplifier() + 1, 3);
				}
				
				$dynamicThreshold = self::BASE_THRESHOLD * (self::SPEED_MULTIPLIERS[$speedLevel] ?? 1.0);
				
				if($speedLevel >= 2){
					$dynamicThreshold += 0.05 * ($speedLevel - 1);
				}
				
				if($distance >= $dynamicThreshold){
					if(++$this->preVL >= 4){
						$roundedDist = round($distance, 2);
						$this->fail($user, 
							'dist=' . $distance . ' buff=' . $this->preVL . ' speed=' . $speedLevel, 
							'dist=' . $roundedDist . ' speed=' . $speedLevel
						);
						$this->preVL = min($this->preVL, 5);
					}
				}else{
					$reduction = $speedLevel > 0 ? 0.035 : 0.025;
					$this->preVL = max($this->preVL - $reduction, 0);
				}
				
				if($this->isDebug($user)){
					$color = $distance > $dynamicThreshold ? TextFormat::RED : TextFormat::WHITE;
					$user->sendMessage($color . 'dist=' . round($distance, 3) . ' buff=' . $this->preVL . ' threshold=' . round($dynamicThreshold, 3) . ' speed=' . $speedLevel);
				}
			}
			$this->awaitingMove = false;
		}
	}
}