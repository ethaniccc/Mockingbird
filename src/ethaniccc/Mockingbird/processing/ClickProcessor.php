<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class ClickProcessor extends Processor{

    public $clicks = [];
    public $timeSamples = [];
    public $tickSamples = [];
    public $ticks = 0;
    public $lastTime;
    public $tickSpeed = 0;
    public $cps = 0;

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
            $this->cps = count($this->clicks);
            $clickTime = microtime(true) - $this->lastTime;
            $this->lastTime = microtime(true);
            $tickSpeed = $this->tickSpeed;
            if(count($this->tickSamples) === self::MAX_SAMPLE_SIZE){
                array_shift($this->tickSamples);
            }
            if(count($this->timeSamples) === self::MAX_SAMPLE_SIZE){
                array_shift($this->timeSamples);
            }
            if($tickSpeed < 4){
                $this->tickSamples[] = $tickSpeed;
            }
            $this->timeSamples[] = $clickTime;
            $this->tickSpeed = 0;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
            ++$this->tickSpeed;
        }
    }

    public function getTickSamples(int $samples) : array{
        return array_slice($this->tickSamples, 0, $samples);
    }

    public function getTimeSamples(int $samples) : array{
        return array_slice($this->tickSamples, 0, $samples);
    }

}