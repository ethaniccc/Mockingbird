<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\PredictionUtils;
use pocketmine\block\Ice;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\block\PackedIce;

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

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $theoreticalOnGround = fmod(($posY = round($user->moveData->location->y, 4)), 1 / 64) === 0.0;
            if($theoreticalOnGround){
                ++$this->onGroundTicks;
            } else {
                $this->onGroundTicks = 0;
            }
            $horizontalSpeed = MathUtils::hypot($user->moveData->moveDelta->x, $user->moveData->moveDelta->z);
            if($user->timeSinceStoppedFlight >= 20){
                $maxSpeed = $this->onGroundTicks >= 10 ? $this->getSetting('max_speed_on_ground') : $this->getSetting('max_speed_off_ground');
                if(!$user->hasReceivedChunks){
                    $maxSpeed = $this->getSetting('max_speed_off_ground');
                }
                foreach($user->moveData->verticalCollisions as $block){
                    if($block instanceof Ice || $block instanceof PackedIce){
                        $maxSpeed = ($maxSpeed / 0.6) * 0.98;
                        break;
                    }
                }
                if($user->player->getEffect(1) !== null){
                    $amplifier = $user->player->getEffect(1)->getAmplifier() + 1;
                    $maxSpeed += 0.2 * $amplifier;
                    $this->ticksSinceSpeed = 0;
                } else {
                    ++$this->ticksSinceSpeed;
                }
                if($horizontalSpeed > $maxSpeed && $user->timeSinceTeleport >= 10 && $user->timeSinceMotion >= 20 && $user->timeSinceStoppedFlight >= 20 && !$user->player->isSpectator() && $user->timeSinceStoppedGlide >= 10){
                    // player just lost their speed effect
                    if($user->player->getEffect(1) === null && $this->ticksSinceSpeed <= 20){
                        return;
                    }
                    if(++$this->preVL >= 2){
                        $this->fail($user, "speed=$horizontalSpeed max=$maxSpeed tpTime={$user->timeSinceTeleport}");
                    }
                } else {
                    if($horizontalSpeed > 0){
                        $this->preVL = 0;
                        $this->reward($user, 0.02);
                    }
                }
                if($this->isDebug($user) && $horizontalSpeed > 0){
                    $user->sendMessage("speed=$horizontalSpeed max=$maxSpeed");
                }
            }
        }
    }

}