<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Server;

class KillAuraB extends Detection{

    private $lastTick = 0;
    private $ticks = 0;
    private $swung = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if($this->ticks - $this->lastTick >= 4){
                if(!$this->swung){
                    if(++$this->preVL >= 3){
                        $this->fail($user);
                    }
                } else {
                    $this->preVL *= 0.5;
                }
                $this->swung = false;
                $this->lastTick = $this->ticks;
            }
        } elseif($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM){
            $this->swung = true;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
        }
    }

}