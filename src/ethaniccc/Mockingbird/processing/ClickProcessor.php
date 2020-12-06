<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ClickProcessor extends Processor{

    private $clicks = [];
    private $lastTime;
    private $ticks = 0;
    private $tickSpeed = 0;

    public const MAX_SAMPLE_SIZE = 150;

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
            $user->clickData->cps = count($this->clicks);
            $clickTime = microtime(true) - $this->lastTime;
            $user->clickData->timeSpeed = $clickTime;
            $this->lastTime = microtime(true);
            $user->clickData->tickSpeed = $this->tickSpeed;
            if($user->clickData->tickSpeed <= 4){
                $user->clickData->tickSamples->add($user->clickData->tickSpeed);
            }
            if($clickTime < 0.2){
                $user->clickData->timeSamples->add($clickTime);
            }
            $this->tickSpeed = 0;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
            ++$this->tickSpeed;
        }
    }

}