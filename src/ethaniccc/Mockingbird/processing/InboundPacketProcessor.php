<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\MathUtils;
use ethaniccc\Mockingbird\utils\PacketUtils;
use pocketmine\block\Block;
use pocketmine\block\Cobweb;
use pocketmine\block\Liquid;
use pocketmine\entity\Effect;
use pocketmine\level\Location;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\Player;
use pocketmine\Server;

class InboundPacketProcessor extends Processor{

    public function __construct(User $user){
        parent::__construct($user);
        $user->hitData->lastTick = Server::getInstance()->getTick();
        $this->lastTime = microtime(true);
    }

    public function process(DataPacket $packet) : void{
        $user = $this->user;
        switch($packet->pid()){
            case PlayerAuthInputPacket::NETWORK_ID:
                /** @var PlayerAuthInputPacket $packet */
                if(!$user->loggedIn){
                    return;
                }
                $shouldHandle = true;
                $location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->getYaw(), $packet->getPitch());
                // $user->locationHistory->addLocation($location);
                $user->moveData->lastLocation = $user->moveData->location;
                $user->moveData->location = $location;
                $user->moveData->lastYaw = $user->moveData->yaw;
                $user->moveData->lastPitch = $user->moveData->pitch;
                $user->moveData->yaw = fmod($location->yaw, 360);
                $user->moveData->pitch = fmod($location->pitch, 360);
                $hasMoved = $location->distanceSquared($user->moveData->lastLocation) > 0.0 || $user->moveData->pitch !== $user->moveData->lastPitch || $user->moveData->yaw !== $user->moveData->lastYaw;
                $user->moveData->isMoving = $hasMoved;
                unset($user->moveData->AABB);
                $user->moveData->AABB = AABB::from($user);
                $movePacket = PacketUtils::playerAuthToMovePlayer($packet, $user);
                if($user->moveData->awaitingTeleport){
                    if($packet->getPosition()->subtract($user->moveData->teleportPos)->length() <= 2){
                        // The user has received the teleport
                        $user->moveData->awaitingTeleport = false;
                    } else {
                        $shouldHandle = false;
                    }
                }
                $user->moveData->awaitingTeleport ? $user->timeSinceTeleport = 0 : ++$user->timeSinceTeleport;
                if($user->timeSinceTeleport > 0 && $hasMoved){
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
                if($user->player->isOnline()){
                    ++$user->timeSinceJoin;
                } else {
                    $user->timeSinceJoin = 0;
                }
                ++$user->timeSinceMotion;
                if(!$user->player->isFlying()){
                    ++$user->timeSinceStoppedFlight;
                } else {
                    $user->timeSinceStoppedFlight = 0;
                }
                if($user->isGliding || $user->player->isSpectator() || $user->player->isImmobile()){
                    $user->timeSinceStoppedGlide = 0;
                } else {
                    ++$user->timeSinceStoppedGlide;
                }
                // 24 is the hardcoded effect ID for slow falling
                if($user->player->getEffect(Effect::LEVITATION) !== null || $user->player->getEffect(24) !== null){
                    $user->moveData->levitationTicks = 0;
                } else {
                    ++$user->moveData->levitationTicks;
                }
                if($location->y > -39.5){
                    ++$user->moveData->ticksSinceInVoid;
                } else {
                    $user->moveData->ticksSinceInVoid = 0;
                }
                ++$user->timeSinceLastBlockPlace;
                if($hasMoved){
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
                    // debug for block AABB - (RESOURCE INTENSIVE)
                    if($user->debugChannel === 'blockbb'){
                        $expandedAABB = $user->moveData->AABB->clone()->expand(4, 4, 4);
                        $distance = PHP_INT_MAX; $target = null;
                        $ray = Ray::fromUser($user);
                        $minX = (int) floor($expandedAABB->minX - 1);
                        $minY = (int) floor($expandedAABB->minY - 1);
                        $minZ = (int) floor($expandedAABB->minZ - 1);
                        $maxX = (int) floor($expandedAABB->maxX + 1);
                        $maxY = (int) floor($expandedAABB->maxY + 1);
                        $maxZ = (int) floor($expandedAABB->maxZ + 1);
                        for($z = $minZ; $z <= $maxZ; ++$z){
                            for($x = $minX; $x <= $maxX; ++$x){
                                for($y = $minY; $y <= $maxY; ++$y){
                                    $block = $user->player->getLevelNonNull()->getBlockAt($x, $y, $z);
                                    if($block->getId() !== 0){
                                        $AABB = AABB::fromBlock($block);
                                        if(($dist = $AABB->collidesRay($ray, 7)) !== -69.0){
                                            if($dist < $distance){
                                                $distance = $dist;
                                                $target = $block;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if($target instanceof Block){
                            $AABB = AABB::fromBlock($target);
                            foreach($AABB->getCornerVectors() as $cornerVector){
                                $user->player->getLevelNonNull()->addParticle(new DustParticle($cornerVector, 0, 255, 255));
                            }
                        }
                    }
                }
                $user->moveData->onGround = $movePacket->onGround;
                if($movePacket->onGround){
                    ++$user->moveData->onGroundTicks;
                    $user->moveData->offGroundTicks = 0;
                    $user->moveData->lastOnGroundLocation = $location;
                } else {
                    ++$user->moveData->offGroundTicks;
                    $user->moveData->onGroundTicks = 0;
                }
                if($hasMoved){
                    $user->moveData->blockBelow = $user->player->getLevel()->getBlock($location->subtract(0, (1/64), 0));
                    $user->moveData->blockAbove = $user->player->getLevel()->getBlock($location->add(0, 2 + (1/64), 0));
                    $user->moveData->directionVector = MathUtils::directionVectorFromValues($user->moveData->yaw, $user->moveData->pitch);
                }
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
                // shouldHandle will be false if the player isn't near the teleport position
                if($shouldHandle){
                    if($hasMoved){
                        // only handle if the move delta is greater than 0 so PlayerMoveEvent isn't spammed
                        if($user->debugChannel === "onground"){
                            $serverGround = $user->player->isOnGround() ? 'true' : 'false';
                            $otherGround = $movePacket->onGround ? 'true' : 'false';
                            $user->sendMessage('pmmp=' . $serverGround . ' mb=' . $otherGround);
                        }
                        $user->player->handleMovePlayer($movePacket);
                    }
                }
                $user->tickProcessor->process($packet);
                ++$this->tickSpeed;
                // $user->testProcessor->process($packet);
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                switch($packet->transactionType){
                    case InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY:
                        switch($packet->trData->actionType){
                            case InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK:
                                $user->hitData->attackPos = $packet->trData->playerPos;
                                $user->hitData->lastTargetEntity = $user->hitData->targetEntity;
                                $user->hitData->targetEntity = $user->player->getLevelNonNull()->getEntity($packet->trData->entityRuntimeId);
                                $user->hitData->inCooldown = Server::getInstance()->getTick() - $user->hitData->lastTick < 10;
                                if(!$user->hitData->inCooldown){
                                    $user->timeSinceAttack = 0;
                                    $user->hitData->lastTick = Server::getInstance()->getTick();
                                }
                                break;
                        }
                        $this->handleClick();
                        break;
                    case InventoryTransactionPacket::TYPE_USE_ITEM:
                        switch($packet->trData->actionType){
                            case InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK:
                                // the user wants to place a block, vadilate what the user wants to do
                                $valid = true;
                                $distance = $user->moveData->location->add(0, $user->isSneaking ? 1.54 : 1.62, 0)->distance($packet->trData->clickPos);
                                if($user->debugChannel === 'blockdist'){
                                    $user->sendMessage("block place dist=$distance");
                                }
                                if($valid){
                                    $user->timeSinceLastBlockPlace = 0;
                                }
                                break;
                        }
                        break;
                }
                // $user->testProcessor->process($packet);
                break;
            case LevelSoundEventPacket::NETWORK_ID:
                /** @var LevelSoundEventPacket $packet */
                switch($packet->sound){
                    case LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE:
                        $this->handleClick();
                        break;
                }
                break;
            case NetworkStackLatencyPacket::NETWORK_ID:
                /** @var NetworkStackLatencyPacket $packet */
                if($packet->timestamp === $user->latencyPacket->timestamp){
                    $user->responded = true;
                    $user->transactionLatency = round((microtime(true) - $user->lastSentNetworkLatencyTime) * 1000, 0);
                    if($user->debugChannel === 'latency'){
                        $user->sendMessage("pmmp={$user->player->getPing()} latency={$user->transactionLatency}");
                    }
                    $pk = new NetworkStackLatencyPacket();
                    $pk->needResponse = true; $pk->timestamp = mt_rand(100000, 10000000) * 1000;
                    $user->latencyPacket = $pk;
                } elseif($packet->timestamp === $user->chunkResponsePacket->timestamp){
                    $user->hasReceivedChunks = true;
                    if($user->debugChannel === 'receive-chunk'){
                        $user->sendMessage('received chunks');
                    }
                    $pk = new NetworkStackLatencyPacket();
                    $pk->needResponse = true; $pk->timestamp = $user->latencyPacket->timestamp + mt_rand(-10000, 10000) * 1000;
                    $user->chunkResponsePacket = $pk;
                }
                // $user->testProcessor->process($packet);
                break;
            case LoginPacket::NETWORK_ID:
                /** @var LoginPacket $packet */
                $user->isDesktop = !in_array($packet->clientData["DeviceOS"], [DeviceOS::AMAZON, DeviceOS::ANDROID, DeviceOS::IOS]);
                try{
                    $data = $packet->chainData;
                    $parts = explode(".", $data['chain'][2]);
                    $jwt = json_decode(base64_decode($parts[1]), true);
                    $id = $jwt['extraData']['titleId'];
                    $user->win10 = ($id === "896928775");
                } catch(\Exception $e){}
                break;
            case PlayerActionPacket::NETWORK_ID:
                /** @var PlayerActionPacket $packet */
                switch($packet->action){
                    case PlayerActionPacket::ACTION_START_SPRINT:
                        $user->isSprinting = true;
                        break;
                    case PlayerActionPacket::ACTION_STOP_SPRINT:
                        $user->isSprinting = false;
                        break;
                    case PlayerActionPacket::ACTION_START_SNEAK:
                        $user->isSneaking = true;
                        break;
                    case PlayerActionPacket::ACTION_STOP_SNEAK:
                        $user->isSneaking = false;
                        break;
                    case PlayerActionPacket::ACTION_START_GLIDE:
                        $user->player->setGenericFlag(Player::DATA_FLAG_GLIDING, true);
                        $user->isGliding = true;
                        break;
                    case PlayerActionPacket::ACTION_STOP_GLIDE:
                        $user->player->setGenericFlag(Player::DATA_FLAG_GLIDING, false);
                        $user->isGliding = false;
                        break;
                }
                break;
            case SetLocalPlayerAsInitializedPacket::NETWORK_ID:
                $user->loggedIn = true;
                if($user->player->hasPermission('mockingbird.alerts') && Mockingbird::getInstance()->getConfig()->get('alerts_default')){
                    $user->alerts = true;
                }
                $user->player->dataPacket($user->latencyPacket);
                $user->lastSentNetworkLatencyTime = microtime(true);
                $user->responded = false;
                break;
        }
    }

    private $clicks = [];
    private $lastTime;
    private $tickSpeed = 0;

    private function handleClick() : void{
        $user = $this->user;
        $currentTick = $user->tickData->currentTick;
        $this->clicks[] = $currentTick;
        $this->clicks = array_filter($this->clicks, function(int $t) use ($currentTick) : bool{
            return $currentTick - $t <= 20;
        });
        $user->clickData->cps = count($this->clicks);
        $clickTime = microtime(true) - $this->lastTime;
        $user->clickData->timeSpeed = $clickTime;
        $this->lastTime = microtime(true);
        $user->clickData->tickSpeed = $this->tickSpeed;
        if($user->clickData->tickSpeed <= 4){
            $user->clickData->tickSamples->add($user->clickData->tickSpeed);
        }
        if($clickTime < 0.2){
            $user->clickData->timeSamples->add($clickTime);
        }
        $this->tickSpeed = 0;
    }

}