<?php

namespace ethaniccc\Mockingbird\detections\combat\hitbox;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class HitboxA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 10;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handle(DataPacket $packet, User $user): void{
        if($user->isDesktop && $packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK && $user->isDesktop && !$user->player->isCreative()){
            if(!$user->hitData->rayCollides){
                if(++$this->preVL >= 8){
                    $this->fail($user);
                    $this->preVL = min($this->preVL, 12);
                }
            } else {
                $this->reward($user, 0.995);
                $this->preVL -= $this->preVL > 0 ? 1 : 0;
            }
        }
    }

}