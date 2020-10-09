<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class MoveProcessor extends Processor{

    private $isOnGround;
    private $ticks = 0;

    public function __construct(User $user){
        parent::__construct($user);
        $this->isOnGround = function(User $user){
            // thank you @very nice name#6789, bounding boxes are yummy!
            $position = $user->location;
            if($position !== null){
                $AABB = AABB::fromPosition($position);
                $AABB->minY -= 0.01;
                $minX = (int) floor($AABB->minX - 1);
                $minY = (int) floor($AABB->minY - 1);
                $minZ = (int) floor($AABB->minZ - 1);
                $maxX = (int) floor($AABB->maxX + 1);
                $maxY = (int) floor($AABB->maxY + 1);
                $maxZ = (int) floor($AABB->maxZ + 1);
                for($z = $minZ; $z <= $maxZ; $z++){
                    for($x = $minX; $x <= $maxX; $x++){
                        for($y = $minY; $y <= $maxY; $y++){
                            $block = $user->player->getLevel()->getBlockAt($x, $y, $z);
                            if($block->getId() !== 0){
                                $collisionBoxes = $block->getCollisionBoxes();
                                if(!empty($collisionBoxes)){
                                    foreach($collisionBoxes as $bb2){
                                        if($AABB->intersectsWith($bb2)){
                                            return true;
                                        }
                                    }
                                } else {
                                    if(AABB::fromBlock($block)->intersectsWith($AABB)){
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return false;
        };
    }

    public function process(DataPacket $packet): void{
        if($packet instanceof MovePlayerPacket){
            $user = $this->user;
            if(!$user->loggedIn){
                return;
            }
            $location = Location::fromObject($packet->position->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->yaw, $packet->pitch);
            $user->locationHistory->addLocation($location);
            $user->lastLocation = $user->location;
            $user->location = $location;
            if($user->lastLocation !== null && $packet->mode === MovePlayerPacket::MODE_NORMAL){
                $user->lastMoveDelta = $user->moveDelta;
                $user->moveDelta = $user->location->subtract($user->lastLocation);
            }
            $user->lastYaw = $user->yaw;
            $user->lastPitch = $user->pitch;
            $user->yaw = $packet->yaw;
            $user->pitch = $packet->pitch;
            $user->lastYawDelta = $user->yawDelta;
            $user->lastPitchDelta = $user->pitchDelta;
            $user->yawDelta = abs($user->yaw - $user->lastYaw);
            $user->pitchDelta = abs($user->pitch - $user->lastPitch);
            if(in_array($packet->mode, [MovePlayerPacket::MODE_RESET, MovePlayerPacket::MODE_TELEPORT])){
                $user->timeSinceTeleport = 0;
            } else {
                ++$user->timeSinceTeleport;
            }
            ++$user->timeSinceDamage;
            ++$user->timeSinceMotion;
            ++$user->timeSinceJoin;
            ++$user->timeSinceAttack;
            $user->serverOnGround = ($this->isOnGround)($user);
            $user->clientOnGround = $packet->onGround;
            if($user->serverOnGround){
                $user->offGroundTicks = 0;
                ++$user->onGroundTicks;
                $user->lastOnGroundLocation = $location;
            } else {
                $user->onGroundTicks = 0;
                ++$user->offGroundTicks;
            }
            $AABB = AABB::from($user);
            $AABB->maxY += 0.01;
            $user->blockAbove = $user->player->getLevelNonNull()->getCollisionBlocks($AABB, true)[0] ?? null;
            $AABB->maxY -= 0.01;
            $AABB->minX -= 0.01;
            $user->blockBelow = $user->player->getLevelNonNull()->getCollisionBlocks($AABB, true)[0] ?? null;
            if(microtime(true) - $user->lastSentNetworkLatencyTime >= 1){
                if(++$this->ticks >= 20){
                    $user->lastSentNetworkLatencyTime = microtime(true);
                    $pk = new NetworkStackLatencyPacket();
                    $pk->timestamp = 1000;
                    $pk->needResponse = true;
                    $user->player->dataPacket($pk);
                }
            } else {
                $this->ticks = 0;
            }
        }
    }

}