<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class AutoClickerD extends Detection{

    private $clicks = 0;
    private $samples = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 30;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if($user->clickData->tickSpeed <= 4){
                if(++$this->clicks === 30){
                    $samples = $user->clickData->getTickSamples(30);
                    if(count($samples) === 30){
                        $kurtosis = MathUtils::getKurtosis($samples);
                        $skewness = MathUtils::getSkewness($samples);
                        $outlierPair = MathUtils::getOutliers($samples);
                        $outliers = count($outlierPair->x) + count($outlierPair->y);
                        $this->samples[] = "kurtosis=$kurtosis skewness=$skewness outliers=$outliers";
                        if(count($this->samples) === $this->getSetting("samples") + 1){
                            array_shift($this->samples);
                        }
                        $duplicates = count($this->samples) - count(array_unique($this->samples));
                        if($duplicates >= $this->getSetting("duplicate_max")){
                            // having the same kurtosis, skewness, and outliers is highly unlikely
                            if(++$this->preVL >= 2){
                                $this->fail($user, "duplicates=$duplicates");
                            }
                        } else {
                            $this->preVL = 0;
                        }
                        if($this->isDebug($user)){
                            $user->sendMessage("duplicates=$duplicates kurtosis=$kurtosis skewness=$skewness outliers=$outliers");
                        }
                    }
                    $this->clicks = 0;
                }
            }
        }
    }

}