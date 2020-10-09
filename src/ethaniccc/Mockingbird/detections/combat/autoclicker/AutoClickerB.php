<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class AutoClickerB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            $timeDiff = $user->clickTime;
            $cps = $user->cps;
            $allowed = $this->getSetting("max_cps");
            $allowed += (int) (microtime(true) - $user->lastSentNetworkLatencyTime) / 100;
            if($cps > $allowed){
                if(++$this->preVL >= 5){
                    $this->fail($user, "{$user->player->getName()}: cps: $cps, allowed: $allowed");
                }
            } else {
                $this->preVL *= 0.65;
                $this->reward($user, 0.95);
            }
        }
    }

}