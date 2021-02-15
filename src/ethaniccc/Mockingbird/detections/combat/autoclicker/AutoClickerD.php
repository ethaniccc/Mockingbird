<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class AutoClickerD
 * @package ethaniccc\Mockingbird\detections\combat\autoclicker
 * AutoClickerD gets multiple samples of kurtosis, skewness, and outliers from multiple click speed samples.
 * Then it checks how many duplicated kurtosis, skewness, and outlier samples there are. If this value surpasses
 * the maximum duplicate count (4), then the user is most likely cheating and this check flags.
 */
class AutoClickerD extends Detection{

    private $clicks = 0;
    private $samples;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 30;
        // make the cheating probability always high
        $this->lowMax = 0; $this->mediumMax = 0;
        $this->samples = new EvictingList(10);
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if($user->clickData->tickSpeed <= 4){
                if(++$this->clicks === 30){
                    $samples = $user->clickData->getTickSamples(30);
                    if(count($samples) === 30){
                        $kurtosis = MathUtils::getKurtosis($samples);
                        $skewness = MathUtils::getSkewness($samples);
                        $outliers = MathUtils::getOutliers($samples);
                        $outliers = count($outliers->getX()) + count($outliers->getY());
                        $this->samples->add("kurtosis=$kurtosis skewness=$skewness outliers=$outliers");
                        $duplicates = $this->samples->duplicates();
                        if($user->clickData->cps >= 10 && $duplicates >= $this->getSetting("duplicate_max")){
                            // unless you can consistently click the same like you're god, you're not going to flag this.
                            $this->fail($user, "duplicates=$duplicates", "cps={$user->clickData->cps}");
                            $this->samples->clear();
                        } else {
                            $this->preVL = max($this->preVL - 2.5, 0);
                            $this->reward($user, 0.03);
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