<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AutoClickerC extends Detection{

    private $movements = 0;
    private $delaySamples = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 45;
        $this->lowMax = 2;
        $this->mediumMax = 4;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if($this->movements <= 4){
                $this->delaySamples[] = $this->movements;
                if(count($this->delaySamples) === $this->getSetting("samples")){
                    $skewness = MathUtils::getSkewness($this->delaySamples);
                    $kurtosis = MathUtils::getKurtosis($this->delaySamples);
                    if($skewness <= $this->getSetting("skewness") && $kurtosis < $this->getSetting("kurtosis")){
                        $this->fail($user, "skewness=$skewness kurtosis=$kurtosis probability={$this->getCheatProbability()}");
                    }
                    $this->delaySamples = [];
                }
            }
            $this->movements = 0;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->movements;
        }
    }

}