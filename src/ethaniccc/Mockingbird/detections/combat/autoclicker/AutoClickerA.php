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
        $this->preVL = 1;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) && $user->isDesktop){
            if($user->clickData->tickSpeed <= 4 && ++$this->clicks >= 20){
                $deviation = MathUtils::getDeviation($user->clickData->getTickSamples(20));
                $diff = abs($deviation - $this->lastDeviation);
                if($diff === 0.0){
                    $this->preVL = max($this->preVL - 0.25, 0);
                    if($this->preVL < 0.25){
                        $this->fail($user, "diff=$diff cps={$user->clickData->cps}");
                    }
                } else {
                    $this->preVL = min($this->preVL + 0.05, 2);
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