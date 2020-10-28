<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ClickProcessor extends Processor{

    private $clicks = [];
    private $ticks = 0;
    private $lastTime;

    public function __construct(User $user){
        parent::__construct($user);
        $this->lastTime = microtime(true);
    }

    public function process(DataPacket $packet): void{
        $user = $this->user;
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            $currentTick = $this->ticks;
            $this->clicks[] = $currentTick;
            $this->clicks = array_filter($this->clicks, function(int $t) use ($currentTick) : bool{
               return $currentTick - $t <= 20;
            });
            $user->cps = count($this->clicks);
            $user->clickTime = microtime(true) - $this->lastTime;
            $this->lastTime = microtime(true);
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
        }
    }

}