<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AutoClickerA extends Detection{

    private $ticks = 0, $lastDeviation;
    private $speeds = [];

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 20;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) && $user->isDesktop){
            if($this->ticks < 5 && $user->isDesktop){
                $speed = $this->ticks * 50;
                $this->speeds[] = $speed;
                if(count($this->speeds) === $this->getSetting("samples")){
                    $deviation = MathUtils::getDeviation($this->speeds);
                    if($this->lastDeviation !== null){
                        $deviationDiff = abs($this->lastDeviation - $deviation);
                        if($deviationDiff <= 0.85 && $deviation < 27.5 && $user->cps >= $this->getSetting("required_cps")){
                            if(++$this->preVL >= 3){
                                $this->fail($user, "deviation=$deviation, equalness=$deviationDiff cps={$user->cps} probability={$this->getCheatProbability()}");
                            }
                        } elseif($deviation <= 9 && $user->cps >= 10) {
                            // impossible consistency - most likely a 10cps autoclicker
                            $this->fail($user, "deviation=$deviation cps={$user->cps}");
                        } else {
                            $this->preVL -= $this->preVL > 0 ? 1 : 0;
                            $this->reward($user, 0.995);
                        }
                    }
                    $this->speeds = [];
                    $this->lastDeviation = $deviation;
                }
            }
            $this->ticks = 0;
        } elseif($packet instanceof PlayerAuthInputPacket){
            ++$this->ticks;
        }
    }

}