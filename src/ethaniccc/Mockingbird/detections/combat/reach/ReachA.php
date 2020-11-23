<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class ReachA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 20;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket
        && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY
        && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK
        && $user->isDesktop && !$user->player->isCreative() && $user->hitData->rayCollides){
            if($user->hitData->rayDistance > $this->getSetting("max_reach")){
                if(++$this->preVL >= 10){
                    $this->fail($user, "distance={$user->hitData->rayDistance} buffer={$this->preVL}");
                    $this->preVL = min($this->preVL, 15);
                }
            } else {
                $this->preVL -= $this->preVL > 0 ? 1 : 0;
            }
        }
    }

}