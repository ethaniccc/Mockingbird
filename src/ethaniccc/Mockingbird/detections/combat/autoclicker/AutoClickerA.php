<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class AutoClickerA extends Detection{

    private $times = [];
    private $passTicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) && $user->isDesktop){
            $clickTime = $user->clickTime;
            if($clickTime > 0.25){
                return;
            }
            if(count($this->times) === $this->getSetting("samples")){
                array_shift($this->times);
            }
            $this->times[] = $clickTime;
            $deviation = MathUtils::getDeviation($this->times) * 1000;
            if($user->cps >= $this->getSetting("required_cps") && $deviation <= $this->getSetting("consistency")){
                $this->passTicks *= 0.55;
                if(++$this->preVL >= 3){
                    $this->fail($user, "d: $deviation, cps: {$user->cps} rCPS: {$this->getSetting("required_cps")}, rC: {$this->getSetting("consistency")}");
                }
            } else {
                $this->preVL *= 0.9;
                ++$this->passTicks;
                if($this->passTicks >= $this->getSetting("samples")){
                    $this->reward($user, 0.99);
                }
            }
        }
    }

}