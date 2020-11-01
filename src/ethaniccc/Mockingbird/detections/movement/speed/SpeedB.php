<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\block\Ice;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\utils\TextFormat;

class SpeedB extends Detection implements CancellableMovement{

    private $speeds = [];
    private $onGroundTicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $theoreticalOnGround = fmod(($posY = round($user->location->y - 1.62, 4)), 1 / 64) === 0.0;
            if($theoreticalOnGround){
                ++$this->onGroundTicks;
            } else {
                $this->onGroundTicks = 0;
            }
            $horizontalSpeed = hypot($user->moveDelta->x, $user->moveDelta->z);
            if(!$user->player->isFlying()
            && $user->blockAbove === null){
                $maxSpeed = $this->onGroundTicks >= 5 ? $this->getSetting("max_speed_on_ground") : $this->getSetting("max_speed_off_ground");
                if($user->blockBelow instanceof Ice){
                    $maxSpeed *= 5/3;
                }
                if($user->player->getEffect(1) !== null){
                    $amplifier = $user->player->getEffect(1)->getAmplifier() + 1;
                    $maxSpeed += 0.2 * $amplifier;
                }
                if($horizontalSpeed > $maxSpeed && $user->timeSinceTeleport >= 10){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "speed=$horizontalSpeed tpTime={$user->timeSinceTeleport}");
                    }
                }
            }
        }
    }

}