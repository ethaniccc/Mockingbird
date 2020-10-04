<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class ClickProcessor extends Processor{

    private $clicks = [], $lastClickTime;

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        $user = $this->user;
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            $currentTime = microtime(true);
            array_unshift($this->clicks, $currentTime);
            $cps = 0;
            if(!empty($this->clicks)){
                $cps = count(array_filter($this->clicks, function(float $t) use ($currentTime){
                    return $currentTime - $t <= 1;
                }));
            }
            $user->cps = $cps;
            $user->clickTime = $currentTime - ($this->lastClickTime ?? $currentTime);
            $this->lastClickTime = $currentTime;
        }
    }

}