<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class KillAuraA extends Detection{

    private $entities = 0;
    private $lastEntity;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if($packet->trData->entityRuntimeId !== $this->lastEntity){
                ++$this->entities;
                if($this->entities > 1){
                    $this->fail($user, "entities={$this->entities}");
                }
            }
            $this->lastEntity = $packet->trData->entityRuntimeId;
            if($this->isDebug($user)){
                $user->sendMessage("entities={$this->entities}");
            }
        } elseif($packet instanceof PlayerAuthInputPacket){
            $this->entities = 0;
        }
    }

}