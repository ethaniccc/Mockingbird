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
        $this->vlThreshold = 20;
        $this->lowMax = 2;
        $this->mediumMax = 4;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) && $user->isDesktop){
            if($user->clickData->tickSpeed <= 4){
                if(++$this->clicks === $this->getSetting("samples")){
                    $data = $user->clickData;
                    $samples = $data->getTickSamples($this->getSetting("samples"));
                    foreach($samples as $key => $value){
                        $samples[$key] = $value * 50;
                    }
                    $deviation = MathUtils::getDeviation($samples);
                    $deviationDiff = abs($deviation - $this->lastDeviation);
                    if($deviation < 28 && $deviationDiff <= $this->getSetting("consistency") && $data->cps >= $this->getSetting("required_cps")){
                        if(++$this->preVL >= 3){
                            $this->fail($user, "deviation=$deviation, diff=$deviationDiff");
                        }
                    } else {
                        $this->preVL = 0;
                    }
                    if($this->isDebug($user)){
                        $user->sendMessage("diff=$deviationDiff");
                    }
                    $this->lastDeviation = $deviation;
                    $this->clicks = 0;
                }
            }
        }
    }

}