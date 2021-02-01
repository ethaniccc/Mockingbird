<?php

namespace ethaniccc\Mockingbird\detections\combat\aimassist;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class AimAssistB
 * @package ethaniccc\Mockingbird\detections\combat\aimassist
 * AimAssistB exploits a flaw in some aim-assists where the yawDelta / pitchDelta is rounded.
 * While testing Ascendency's aim-assist, this kind of behaviour occured (see https://media.discordapp.net/attachments/727159224320131133/795739419998289960/unknown.png?width=469&height=523).
 * In the screenshot mentioned above, the yaw and pitch delta seem to be rounded to some extent, and this is where this check comes in.
 */
class AimAssistB extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 10;
        $this->lowMax = 2;
        $this->mediumMax = 3;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        // right now I'm only using this check for win10 players as this isn't tested on other platforms
        if($packet instanceof PlayerAuthInputPacket && $user->win10){
            if($user->moveData->yawDelta > 0.0065){
                $roundedDiff = abs(round($user->moveData->yawDelta, 1) - round($user->moveData->yawDelta, 5));
                if($roundedDiff <= 3E-5){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "roundedYawDiff=$roundedDiff");
                    }
                } else {
                    $this->reward($user, 0.9995);
                    $this->preVL = max($this->preVL - 0.05, 0);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("roundedDiff=$roundedDiff buff={$this->preVL}");
                }
            }
        }
    }

}