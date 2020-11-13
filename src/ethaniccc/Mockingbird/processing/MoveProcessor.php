<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\PacketUtils;
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class MoveProcessor extends Processor{

    private $ticks = 0;

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        if($packet instanceof PlayerAuthInputPacket){
            $user = $this->user;
            if(!$user->loggedIn){
                return;
            }
            $location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->getYaw(), $packet->getPitch());
            $user->locationHistory->addLocation($location);
            $user->moveData->lastLocation = $user->moveData->location;
            $user->moveData->location = $location;
            $user->moveData->lastYaw = $user->moveData->yaw;
            $user->moveData->lastPitch = $user->moveData->pitch;
            $user->moveData->yaw = fmod($location->yaw, 360);
            $user->moveData->pitch = fmod($location->pitch, 360);
            $movePacket = PacketUtils::playerAuthToMovePlayer($packet, $user);
            if($user->timeSinceTeleport > 0){
                $user->moveData->lastMoveDelta = $user->moveData->moveDelta;
                $user->moveData->moveDelta = $user->moveData->location->subtract($user->moveData->lastLocation)->asVector3();
                $user->moveData->lastYawDelta = $user->moveData->yawDelta;
                $user->moveData->lastPitchDelta = $user->moveData->pitchDelta;
                $user->moveData->yawDelta = abs($user->moveData->lastYaw - $user->moveData->yaw);
                $user->moveData->pitchDelta = abs($user->moveData->lastPitch - $user->moveData->pitch);
                $user->moveData->rotated = $user->moveData->yawDelta > 0 || $user->moveData->pitchDelta > 0;
            }
            ++$user->timeSinceTeleport;
            ++$user->timeSinceDamage;
            ++$user->timeSinceAttack;
            ++$user->timeSinceJoin;
            ++$user->timeSinceMotion;
            $user->moveData->onGround = $movePacket->onGround;
            if($user->moveData->onGround){
                ++$user->moveData->onGroundTicks;
                $user->moveData->offGroundTicks = 0;
                $user->moveData->lastOnGroundLocation = $location;
            } else {
                ++$user->moveData->offGroundTicks;
                $user->moveData->onGroundTicks = 0;
            }
            $user->moveData->blockBelow = $user->player->getLevel()->getBlock($location->subtract(0, (1/64), 0));
            $user->moveData->blockAbove = $user->player->getLevel()->getBlock($location->add(0, 2 + (1/64), 0));
            $user->moveData->pressedKeys = [];
            if($packet->getMoveVecZ() > 0){
                $user->moveData->pressedKeys[] = "W";
            } elseif($packet->getMoveVecZ() < 0){
                $user->moveData->pressedKeys[] = "S";
            }
            if($packet->getMoveVecX() > 0){
                $user->moveData->pressedKeys[] = "A";
            } elseif($packet->getMoveVecX() < 0){
                $user->moveData->pressedKeys[] = "D";
            }
            $user->moveData->directionVector = MathUtils::directionVectorFromValues($user->moveData->yaw, $user->moveData->pitch);
            if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1){
                if(++$this->ticks >= 20){
                    $user->player->dataPacket($user->networkStackLatencyPacket);
                    if($this->ticks >= 1000){
                        // yeah no, you're not making a disabler out of this
                        Mockingbird::getInstance()->getScheduler()->scheduleDelayedTask(new KickTask($user, "NetworkStackLatency Timeout (bad connection?) - Rejoin server"), 0);
                    }
                }
            } else {
                $this->ticks = 0;
            }
            // now this is important - otherwise everything will break
            if(!$user->moveData->moveDelta->equals($user->zeroVector) || $user->moveData->yawDelta > 0 || $user->moveData->pitch > 0){
                // only handle if the move delta is greater than 0 so PlayerMoveEvent isn't spammed
                $user->player->handleMovePlayer($movePacket);
            }
        }
    }

}