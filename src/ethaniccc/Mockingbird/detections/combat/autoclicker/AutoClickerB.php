<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class AutoClickerB
 * @package ethaniccc\Mockingbird\detections\combat\autoclicker
 * AutoClickerB checks if the user is clicking too fast. This is one of the simplest checks
 * to make - however may false with certain clicking methods (such as drag-clicking).
 */
class AutoClickerB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 10;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->trData->getTypeId() === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            $cps = $user->clickData->cps;
            $allowed = $this->getSetting('max_cps');
            if($cps > $allowed){
                $this->fail($user, "cps=$cps, allowed=$allowed", "cps=$cps");
            } else {
                $this->reward($user, 0.04);
            }
            if($this->isDebug($user)){
                $user->sendMessage("cps=$cps");
            }
        }
    }

}