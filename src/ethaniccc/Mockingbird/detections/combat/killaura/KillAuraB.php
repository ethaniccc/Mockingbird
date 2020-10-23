<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Server;

class KillAuraB extends Detection{

    private $lastTick = 0;
    private $swung = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
            if(Server::getInstance()->getTick() - $this->lastTick >= 10){
                if(!$this->swung && ++$this->preVL >= 3){
                    $this->fail($user);
                } else {
                    $this->preVL *= 0.5;
                }
                $this->swung = false;
                $this->lastTick = Server::getInstance()->getTick();
            }
        } elseif($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM){
            $this->swung = true;
        }
    }

}