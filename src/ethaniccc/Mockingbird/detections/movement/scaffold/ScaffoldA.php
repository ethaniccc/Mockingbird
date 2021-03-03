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

    /** @var null|Block */
    private $lastPlacedBlock;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK){
            $clickedBlockPos = new Vector3($packet->trData->x, $packet->trData->y, $packet->trData->z);
            $blockClicked = $user->player->getLevel()->getBlock($clickedBlockPos, false, false);
            if($this->lastPlacedBlock !== null){
                $prevDistXZ = MathUtils::hypot($packet->trData->playerPos->x - $this->lastPlacedBlock->x, $packet->trData->playerPos->z - $this->lastPlacedBlock->z);
                $currDistXZ = MathUtils::hypot($packet->trData->playerPos->x - $blockClicked->x, $packet->trData->playerPos->z - $blockClicked->z);
            }
            $this->lastPlacedBlock = $blockClicked;
        }
    }

}