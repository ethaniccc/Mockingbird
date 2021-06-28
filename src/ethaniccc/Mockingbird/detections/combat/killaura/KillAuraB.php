<?php

namespace ethaniccc\Mockingbird\detections\combat\killaura;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

/**
 * Class KillAuraB
 * @package ethaniccc\Mockingbird\detections\combat\killaura
 * KillAuraB checks if the user sends an animation packet with the action being "swing" before hitting the target entity.
 * This flags bad killauras that don't swing before attacking an entity (hello, Toolbox).
 */
class KillAuraB extends Detection{

    private $lastTick = 0;
    private $swung = false;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 10;
        $this->lowMax = 3;
        $this->mediumMax = 4;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket && $packet->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK){
            if($user->tickData->currentTick - $this->lastTick >= 4){
                if(!$this->swung){
                    if(++$this->preVL >= 3){
                        $this->fail($user);
                    }
                } else {
                    $this->reward($user, 0.02);
                    $this->preVL *= 0.5;
                }
                $this->swung = false;
                $this->lastTick = $user->tickData->currentTick;
            }
            if($this->isDebug($user)){
                $tickDiff = $user->tickData->currentTick - $this->lastTick;
                $user->sendMessage("tickDiff=$tickDiff");
            }
        } elseif($packet instanceof AnimatePacket && $packet->action === AnimatePacket::ACTION_SWING_ARM){
            $this->swung = true;
        }
    }

}