<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\PacketUtils;
use pocketmine\block\Cobweb;
use pocketmine\block\Liquid;
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\Player;

class MoveProcessor extends Processor{

    public function __construct(User $user){
        parent::__construct($user);
    }

    public function process(DataPacket $packet): void{
        if($packet instanceof PlayerAuthInputPacket){
            $user = $this->user;
            if(!$user->loggedIn){
                return;
            }
            $shouldHandle = true;
            $location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->getYaw(), $packet->getPitch());
            // $user->locationHistory->addLocation($location);
            $user->moveData->lastLocation = $user->moveData->location;
            $user->moveData->location = $location;
            unset($user->moveData->AABB);
            $user->moveData->AABB = AABB::from($user);
            $user->moveData->lastYaw = $user->moveData->yaw;
            $user->moveData->lastPitch = $user->moveData->pitch;
            $user->moveData->yaw = fmod($location->yaw, 360);
            $user->moveData->pitch = fmod($location->pitch, 360);
            $movePacket = PacketUtils::playerAuthToMovePlayer($packet, $user);
            if($user->moveData->appendingTeleport){
                if($packet->getPosition()->subtract($user->moveData->teleportPos)->length() <= 2){
                    // The user has received the teleport
                    $user->moveData->appendingTeleport = false;
                } else {
                    $shouldHandle = false;
                }
            }
            $user->moveData->appendingTeleport ? $user->timeSinceTeleport = 0 : ++$user->timeSinceTeleport;
            if($user->timeSinceTeleport > 0){
                $user->moveData->lastMoveDelta = $user->moveData->moveDelta;
                $user->moveData->moveDelta = $user->moveData->location->subtract($user->moveData->lastLocation)->asVector3();
                $user->moveData->lastYawDelta = $user->moveData->yawDelta;
                $user->moveData->lastPitchDelta = $user->moveData->pitchDelta;
                $user->moveData->yawDelta = abs($user->moveData->lastYaw - $user->moveData->yaw);
                $user->moveData->pitchDelta = abs($user->moveData->lastPitch - $user->moveData->pitch);
                $user->moveData->rotated = $user->moveData->yawDelta > 0 || $user->moveData->pitchDelta > 0;
            }
            ++$user->timeSinceDamage;
            ++$user->timeSinceAttack;
            ++$user->timeSinceJoin;
            ++$user->timeSinceMotion;
            if(!$user->player->isFlying()){
                ++$user->timeSinceStoppedFlight;
            } else {
                $user->timeSinceStoppedFlight = 0;
            }
            if(!$user->player->getGenericFlag(Player::DATA_FLAG_GLIDING)){
                ++$user->timeSinceStoppedGlide;
            } else {
                $user->timeSinceStoppedGlide = 0;
            }
            ++$user->timeSinceLastBlockPlace;
            $liquids = 0;
            $cobweb = 0;
            foreach($user->player->getBlocksAround() as $block){
                if($block instanceof Liquid){
                    $liquids++;
                } elseif($block instanceof Cobweb){
                    $cobweb++;
                }
            }
            if($liquids > 0){
                $user->moveData->liquidTicks = 0;
            } else {
                ++$user->moveData->liquidTicks;
            }
            if($cobweb > 0){
                $user->moveData->cobwebTicks = 0;
            } else {
                ++$user->moveData->cobwebTicks;
            }
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
            // shouldHandle will be false if the player isn't near the teleport position
            if($shouldHandle){
                // now this is important - otherwise everything will break
                if(!$user->moveData->moveDelta->equals($user->zeroVector) || $user->moveData->yawDelta > 0 || $user->moveData->pitch > 0){
                    // only handle if the move delta is greater than 0 so PlayerMoveEvent isn't spammed
                    $user->player->handleMovePlayer($movePacket);
                }
            }
            $user->processors["RotationProcessor"]->run();
        }
    }

}