<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

/**
 * Class AutoClickerE
 * @package ethaniccc\Mockingbird\detections\combat\autoclicker
 * This check uses deviation, outliers, and the concept of "trust" to determine
 * if a player is using some sort of autoclicker. This check checks if the deviation and outliers
 * of click samples go below a certain threshold. Once this happens, the pre-violation is raised, and the
 * trust is lowered. If the pre-violations is higher than a certain value, then the check flags. However, if
 * the player passes the check, the trust is increased by 0.05.
 */
class AutoClickerE extends Detection{

    private $clicks = 0;
    private $trust = 1;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 20;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handle(DataPacket $packet, User $user) : void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if($user->clickData->tickSpeed <= 4 && ++$this->clicks === 20){
                $speeds = $user->clickData->getTickSamples(20);
                $deviation = sqrt(MathUtils::getVariance($speeds));
                $pair = MathUtils::getOutliers($speeds);
                $outliers = count($pair->getX()) + count($pair->getY());
                if($deviation <= 0.45 && $outliers <= 1){
                    $this->trust = max($this->trust - 0.25, 0);
                    if(++$this->preVL >= 3){
                        $this->preVL = min($this->preVL, 6);
                        $this->fail($user, "deviation=$deviation outliers=$outliers cps={$user->clickData->cps} buff={$this->preVL}");
                    }
                } else {
                    $this->preVL = max($this->preVL - $this->trust, 0);
                    $this->trust = min($this->trust + 0.05, 3);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("deviation=$deviation outliers=$outliers cps={$user->clickData->cps} trust={$this->trust} buff={$this->preVL}");
                }
                $this->clicks = 0;
            }
        }
    }

}