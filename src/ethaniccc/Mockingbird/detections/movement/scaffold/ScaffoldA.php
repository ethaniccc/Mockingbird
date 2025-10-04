<?php

namespace ethaniccc\Mockingbird\detections\movement\scaffold;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\math\Facing;

class ScaffoldA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK){
            $placedBlockPos = (new Vector3($packet->trData->x, $packet->trData->y, $packet->trData->z))->getSide($packet->trData->face);
            $subVec = $placedBlockPos->subtract($user->moveData->location->asVector3()->floor());
            if($subVec->y === -1 && $subVec->lengthSquared() === 1.0 && $packet->trData->clickPos->distanceSquared($user->zeroVector) === 0.0){
                $this->fail($user);
            }
            if($this->isDebug($user))
                $user->sendMessage("subVec=$subVec click={$packet->trData->clickPos}");
        }
    }

}