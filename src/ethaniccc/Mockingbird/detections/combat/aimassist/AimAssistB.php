<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\SizedList;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class AimAssistB extends Detection{

    private $pitchSamples;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->pitchSamples = new SizedList(20);
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof PlayerAuthInputPacket && $user->win10 && $user->moveData->pitchDelta > 0.0065 && $user->moveData->pitchDelta < 10 && abs($user->moveData->pitch) < 85){
            // TODO: Make an AimAssist check that doesn't flag at 0 sensitivity :C (GCD false flags sensitivity)
        }
    }



}