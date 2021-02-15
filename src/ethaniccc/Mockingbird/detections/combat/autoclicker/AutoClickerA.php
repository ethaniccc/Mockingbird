<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class AutoClickerA
 * @package ethaniccc\Mockingbird\detections\combat\autoclicker
 * AutoClickerA checks if the current deviation of the click speeds (in ticks)
 * is too similar to the last deviation of the previous click speeds. This is common
 * in poorly-made auto-clickers.
 */
class AutoClickerA extends Detection{

    private $clicks = 0;
    private $lastDeviation = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
        $this->lowMax = 2;
        $this->mediumMax = 4;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) && $user->isDesktop){
            if($user->clickData->tickSpeed <= 3 && ++$this->clicks >= 20){
                $samples = $user->clickData->getTickSamples(20);
                $deviation = MathUtils::getDeviation($samples);
                $skewness = MathUtils::getSkewness($samples);
                $diff = abs($deviation - $this->lastDeviation);
                // Skewness was added to here to prevent false-flags when butterfly clicking consistenly, as
                // jitter clicking tends to have a skewness lower than 0, and butterfly clicking has higher skewness.
                if($diff === 0.0 && $skewness <= 0.0 && $user->clickData->cps >= 8){
                    $this->preVL = min($this->preVL + 0.25, 2);
                    if($this->preVL > 0.75){
                        $this->fail($user, "deviation=$deviation diff=$diff cps={$user->clickData->cps}", "cps={$user->clickData->cps}");
                    }
                } else {
                    $this->preVL = max($this->preVL - 0.05, 0);
                    $this->reward($user, 0.1);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("diff=$diff buff={$this->preVL} cps={$user->clickData->cps}");
                }
                $this->lastDeviation = $deviation;
                $this->clicks = 0;
            }
        }
    }

}