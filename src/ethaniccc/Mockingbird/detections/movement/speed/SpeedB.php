<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\block\Ice;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class SpeedB
 * @package ethaniccc\Mockingbird\detections\movement\speed
 * SpeedB is a speed limit check which is made off values I've gotten by testing in-game.
 * This check works well as far as I'm concerned so far.
 */
class SpeedB extends Detection implements CancellableMovement{

    private $onGroundTicks = 0;
    private $ticksSinceSpeed = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $theoreticalOnGround = fmod(($posY = round($user->moveData->location->y, 4)), 1 / 64) === 0.0;
            if($theoreticalOnGround){
                ++$this->onGroundTicks;
            } else {
                $this->onGroundTicks = 0;
            }
            $horizontalSpeed = hypot($user->moveData->moveDelta->x, $user->moveData->moveDelta->z);
            if($user->timeSinceStoppedFlight >= 20
            && $user->moveData->blockAbove->getId() === 0){
                $maxSpeed = $this->onGroundTicks >= 10 ? $this->getSetting("max_speed_on_ground") : $this->getSetting("max_speed_off_ground");
                if($user->moveData->blockBelow instanceof Ice){
                    $maxSpeed *= 5/3;
                }
                if($user->player->getEffect(1) !== null){
                    $amplifier = $user->player->getEffect(1)->getAmplifier() + 1;
                    $maxSpeed += 0.2 * $amplifier;
                    $this->ticksSinceSpeed = 0;
                } else {
                    ++$this->ticksSinceSpeed;
                }
                if($horizontalSpeed > $maxSpeed && $user->timeSinceTeleport >= 10 && $user->timeSinceMotion >= 20 && !$user->player->isFlying() && !$user->player->isSpectator() && !$user->timeSinceStoppedGlide >= 10){
                    // player just lost their speed effect
                    if($user->player->getEffect(1) === null && $this->ticksSinceSpeed <= 20){
                        return;
                    }
                    if(++$this->preVL >= 2){
                        $this->fail($user, "speed=$horizontalSpeed max=$maxSpeed tpTime={$user->timeSinceTeleport}");
                    }
                } else {
                    $this->preVL = 0;
                    $this->reward($user, 0.999);
                }
                if($this->isDebug($user)){
                    $user->sendMessage("speed=$horizontalSpeed max=$maxSpeed");
                }
            }
        }
    }

}