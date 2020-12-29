<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\SizedList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class AutoClickerC
 * @package ethaniccc\Mockingbird\detections\combat\autoclicker
 * AutoClickerC takes some clicking speeds and gets the kurtosis, skewness, and outliers of those
 * click samples (thanks Elevated). If the kurtosis, skewness, and outliers surpass a certain threshold,
 * then the check flags. Note that this check can false (at LOW) with legit players.
 */
class AutoClickerC extends Detection{

    private $clicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 45;
        $this->lowMax = 2;
        $this->mediumMax = 4;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if($user->clickData->tickSpeed <= 4){
                if(++$this->clicks >= $this->getSetting("samples")){
                    $samples = $user->clickData->getTickSamples($this->getSetting("samples"));
                    $kurtosis = MathUtils::getKurtosis($samples); $skewness = MathUtils::getSkewness($samples); $pair = MathUtils::getOutliers($samples);
                    $outliers = count($pair->getX()) + count($pair->getY());
                    if($kurtosis <= $this->getSetting("kurtosis") && $skewness <= $this->getSetting("skewness") && $outliers <= $this->getSetting("outliers")){
                        $this->fail($user, "kurtosis=$kurtosis skewness=$skewness outliers=$outliers cps={$user->clickData->cps}", "cps={$user->clickData->cps}");
                    }
                    if($this->isDebug($user)){
                        $user->sendMessage("kurtosis=$kurtosis skewness=$skewness outliers=$outliers cps={$user->clickData->cps}");
                    }
                    $this->clicks = 0;
                }
            }
        }
    }

}