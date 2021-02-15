<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class KillAuraA
 * @package ethaniccc\Mockingbird\detections\combat\killaura
 * KillAuraA checks if the user is hitting too many entities in the same tick.
 */
class KillAuraA extends Detection{

    private $entities = 0;
    /** @var Entity */
    private $lastEntity;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            $ent = $user->player->getLevelNonNull()->getEntity($packet->trData->entityRuntimeId);
            if($ent !== null && $this->lastEntity !== null && $ent->getId() !== $this->lastEntity->getId() && $ent->distance($this->lastEntity) > 2){
                ++$this->entities;
                if($this->entities > 1){
                    $this->fail($user, "entities={$this->entities}");
                } else {
                    $this->reward($user, 0.075);
                }
            }
            $this->lastEntity = $ent;
            if($this->isDebug($user)){
                $user->sendMessage("entities={$this->entities}");
            }
        } elseif($packet instanceof PlayerAuthInputPacket){
            $this->entities = 0;
        }
    }

}