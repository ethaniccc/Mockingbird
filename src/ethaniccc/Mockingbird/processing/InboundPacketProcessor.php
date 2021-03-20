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
use pocketmine\block\Ladder;
use pocketmine\block\Liquid;
use pocketmine\block\Transparent;
use pocketmine\block\UnknownBlock;
use pocketmine\block\Vine;
use pocketmine\block\Water;
use pocketmine\entity\Effect;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Location;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\Position;
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
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\utils\TextFormat;

class InboundPacketProcessor extends Processor{

    /** @var Vector3[] */
    private $postPendingTeleports = [];

    public function __construct(){
        $this->lastTime = microtime(true);
    }

    public function process(DataPacket $packet, User $user) : void{
        switch($packet->pid()){
            case PlayerAuthInputPacket::NETWORK_ID:
                /** @var PlayerAuthInputPacket $packet */
                if(!$user->loggedIn){
                    return;
                }
                $location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getLevel(), $packet->getYaw(), $packet->getPitch());
                // $user->locationHistory->addLocation($location);
                $user->moveData->lastLocation = $user->moveData->location;
                $user->moveData->location = $location;
                $user->moveData->lastYaw = $user->moveData->yaw;
                $user->moveData->lastPitch = $user->moveData->pitch;
                $user->moveData->yaw = fmod($location->yaw, 360);
                $user->moveData->pitch = fmod($location->pitch, 360);
                $hasMoved = $location->distanceSquared($user->moveData->lastLocation) > 0.0 || abs($user->moveData->pitch - $user->moveData->lastPitch) > 9E-6 || abs($user->moveData->yaw !== $user->moveData->lastYaw) > 9E-6;
                $user->moveData->isMoving = $hasMoved;
                unset($user->moveData->AABB);
                $user->moveData->AABB = AABB::from($user);
                $movePacket = PacketUtils::playerAuthToMovePlayer($packet, $user);
                if($user->moveData->moveDelta->lengthSquared() > 0.0009){
                    if(count($user->outboundProcessor->pendingTeleports) !== 0){
                        foreach($user->outboundProcessor->pendingTeleports as $teleport){
                            if($user->moveData->location->distance($teleport) <= 2){
                                $user->timeSinceTeleport = 0;
                                break;
                            }
                        }
                    }
                }
                ++$user->timeSinceTeleport;
                if($user->timeSinceTeleport > 0 && $hasMoved){
                    $user->moveData->lastMoveDelta = $user->moveData->moveDelta;
                    $user->moveData->moveDelta = $user->moveData->location->subtract($user->moveData->lastLocation)->asVector3();
                    $user->moveData->lastYawDelta = $user->moveData->yawDelta;
                    $user->moveData->lastPitchDelta = $user->moveData->pitchDelta;
                    $user->moveData->yawDelta = abs($user->moveData->lastYaw - $user->moveData->yaw);
                    $user->moveData->pitchDelta = abs($user->moveData->lastPitch - $user->moveData->pitch);
                    $user->moveData->rotated = $user->moveData->yawDelta > 0 || $user->moveData->pitchDelta > 0;
                    if($user->moveData->rotated && $user->debugChannel === 'rotation'){
                        $user->sendMessage('yawDelta=' . $user->moveData->yawDelta . ' pitchDelta=' . $user->moveData->pitchDelta);
                    }
                } else {
                    $user->moveData->lastMoveDelta = $user->moveData->moveDelta;
                    $user->moveData->moveDelta = $user->zeroVector;
                    $user->moveData->lastYawDelta = $user->moveData->yawDelta;
                    $user->moveData->lastPitchDelta = $user->moveData->pitchDelta;
                    $user->moveData->yawDelta = 0.0;
                    $user->moveData->pitchDelta = 0.0;
                    $user->moveData->rotated = false;
                }
                if($user->mouseRecorder !== null && $user->mouseRecorder->isRunning && $user->moveData->yawDelta > 0){
                    $user->mouseRecorder->handleRotation($user->moveData->yawDelta, $user->moveData->pitchDelta);
                    if($user->mouseRecorder->getAdmin()->debugChannel === 'mouse-recorder'){
                        $user->mouseRecorder->getAdmin()->sendMessage('The mouse recording is ' . TextFormat::BOLD . TextFormat::GOLD . round($user->mouseRecorder->getPercentage(), 4) . '%' . TextFormat::RESET . ' done!');
                    }
                    if($user->mouseRecorder->isFinished()){
                        $user->mouseRecorder->finish($user);
                    }
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
                // 27 is the hardcoded effect ID for slow falling (I think...?)
                if($user->player->getEffect(Effect::LEVITATION) !== null || $user->player->getEffect(27) !== null){
                    $user->moveData->levitationTicks = 0;
                } else {
                    ++$user->moveData->levitationTicks;
                }
                if($location->y > -39.5){
                    ++$user->moveData->ticksSinceInVoid;
                } else {
                    $user->moveData->ticksSinceInVoid = 0;
                }
                // 0.03 ^ 2
                if($user->moveData->moveDelta->lengthSquared() > 0.0009){
                    $speed = $user->player->getAttributeMap()->getAttribute(5)->getValue();
                    if($user->debugChannel === 'speed'){
                        $user->sendMessage('speed=' . $speed);
                    }
                    $liquids = 0;
                    $cobweb = 0;
                    $climb = 0;
                    foreach($user->player->getBlocksAround() as $block){
                        if($block instanceof Liquid){
                            $liquids++;
                        } elseif($block instanceof Cobweb){
                            $cobweb++;
                        } elseif($block instanceof Ladder || $block instanceof Vine){
                            $climb++;
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
                    if($climb > 0){
                        $user->moveData->climbableTicks = 0;
                    } else {
                        ++$user->moveData->climbableTicks;
                    }
                    // debug for block AABB - (VERY RESOURCE INTENSIVE)
                    if($user->debugChannel === 'block-bb'){
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
                                        if(($dist = $AABB->collidesRay($ray, 0, 7)) !== -69.0){
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
                // 0.03 ^ 2
                if($user->moveData->moveDelta->lengthSquared() > 0.0009){
                    // should I be worried about performance here?
                    $verticalBlocks = $user->player->getLevel()->getCollisionBlocks($user->moveData->AABB->expandedCopy(0.1, 0.2, 0.1));
                    $horizontalBlocks = $user->player->getLevel()->getCollisionBlocks($user->moveData->AABB->expandedCopy(0.2, -0.1, 0.2));
                    $ghostCollisions = 0;
                    $user->moveData->ghostCollisions = [];
                    $verticalAABB = $user->moveData->AABB->expandedCopy(0.1, 0.2, 0.1);
                    foreach($user->ghostBlocks as $block){
                        if(!$block->canPassThrough() && AABB::fromBlock($block)->intersectsWith($verticalAABB, 0.0001)){
                            $ghostCollisions++;
                            $user->moveData->ghostCollisions[] = $block;
                            break;
                        }
                    }
                    $user->moveData->onGround = count($verticalBlocks) !== 0 || $ghostCollisions > 0;
                    if($user->debugChannel === 'on-ground'){
                        $user->sendMessage('onGround=' . var_export($user->moveData->onGround, true) . ' ghostCollisions=' . $ghostCollisions . ' pmmp=' . var_export($user->player->isOnGround(), true));
                    }
                    $user->moveData->verticalCollisions = $verticalBlocks;
                    $user->moveData->horizontalCollisions = $horizontalBlocks;
                    $user->moveData->isCollidedVertically = count($verticalBlocks) !== 0;
                    $user->moveData->isCollidedHorizontally = count($horizontalBlocks) !== 0;
                }
                if($user->moveData->onGround){
                    ++$user->moveData->onGroundTicks;
                    $user->moveData->offGroundTicks = 0;
                    $user->moveData->lastOnGroundLocation = $location;
                } else {
                    ++$user->moveData->offGroundTicks;
                    $user->moveData->onGroundTicks = 0;
                }
                if($hasMoved){
                    $user->moveData->lastDirectionVector = $user->moveData->directionVector;
                    try{
                        $user->moveData->directionVector = MathUtils::directionVectorFromValues($user->moveData->yaw, $user->moveData->pitch);
                    } catch(\ErrorException $e){
                        $user->moveData->directionVector = clone $user->zeroVector;
                    }
                }
                $user->moveData->pressedKeys = [];
                if($packet->getMoveVecZ() > 0){
                    $user->moveData->pressedKeys[] = 'W';
                } elseif($packet->getMoveVecZ() < 0){
                    $user->moveData->pressedKeys[] = 'S';
                }
                if($packet->getMoveVecX() > 0){
                    $user->moveData->pressedKeys[] = 'A';
                } elseif($packet->getMoveVecX() < 0){
                    $user->moveData->pressedKeys[] = 'D';
                }
                // shouldHandle will be false if the player isn't near the teleport position
                if($hasMoved){
                    // only handle if the move delta is greater than 0 so PlayerMoveEvent isn't spammed
                    if($user->debugChannel === 'onground'){
                        $serverGround = $user->player->isOnGround() ? 'true' : 'false';
                        $otherGround = $movePacket->onGround ? 'true' : 'false';
                        $user->sendMessage('pmmp=' . $serverGround . ' mb=' . $otherGround);
                    }
                    $user->player->handleMovePlayer($movePacket);
                }
                $user->tickProcessor->process($packet, $user);
                ++$this->tickSpeed;
                // $user->testProcessor->process($packet, $user);
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
				if($packet->trData instanceof UseItemOnEntityTransactionData){
					if($packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_ATTACK) {
						$user->hitData->attackPos = $packet->trData->getPlayerPos();
						$user->hitData->lastTargetEntity = $user->hitData->targetEntity;
						$user->hitData->targetEntity = $user->player->getLevelNonNull()->getEntity($packet->trData->getEntityRuntimeId());
						$user->hitData->inCooldown = Server::getInstance()->getTick() - $user->hitData->lastTick < 10;
						if(!$user->hitData->inCooldown){
							$user->timeSinceAttack = 0;
							$user->hitData->lastTick = Server::getInstance()->getTick();
						}
						if($user->hitData->targetEntity !== $user->hitData->lastTargetEntity) {
							$user->tickData->targetLocations = [];
							$user->outboundProcessor->pendingLocations = [];
						}
					}
					$this->handleClick($user);
				}

				if($packet->trData instanceof UseItemTransactionData){
					switch($packet->trData->getActionType()){
						case UseItemTransactionData::ACTION_CLICK_BLOCK:
							/** @var Item $inHand */
							$inHand = $packet->trData->itemInHand;
							$clickedBlockPos = new Vector3($packet->trData->x, $packet->trData->y, $packet->trData->z);
							$blockClicked = $user->player->getLevel()->getBlock($clickedBlockPos, false, false);
							$block = $inHand->getBlock();
							if($inHand->getId() < 0){
								// suck my...
								$block = new UnknownBlock($inHand->getId(), $inHand->getDamage());
							}
							if($block->canBePlaced() || $block instanceof UnknownBlock){
								$placeable = true;
								$block->position(Position::fromObject($clickedBlockPos->getSide($packet->trData->getFace()), $user->player->getLevel()));
								$isGhostBlock = false;
								foreach($user->ghostBlocks as $ghostBlock){
									if($ghostBlock->asVector3()->distanceSquared($block->asVector3()) === 0.0){
										$isGhostBlock = true;
										break;
									}
								}
								if($block->canBePlacedAt($blockClicked, ($packet->trData->clickPos ?? new Vector3(0, 0, 0)), $packet->trData->getFace(), true) && !$isGhostBlock){
									$block->position($blockClicked->asPosition());
								} /* elseif($block->canBePlacedAt($blockClicked, ($packet->trData->clickPos ?? new Vector3(0, 0, 0)), $packet->trData->face, true) && $isGhostBlock){
                                        $user->sendMessage('ghost block placed on ghost block.');
                                    } */
								if($block->isSolid()){
									foreach($block->getCollisionBoxes() as $BB){
										if(count($user->player->getLevel()->getCollidingEntities($BB)) > 0){
											$placeable = false; // an entity in a block
											break;
										}
									}
								}
								if($placeable){
									$user->placedBlocks[] = $block;
									$interactPos = $clickedBlockPos->getSide($packet->trData->getFace())->add($packet->trData->clickPos);
									$distance = $interactPos->distance($user->moveData->location->add($user->isSneaking ? 1.54 : 1.62));
									if($user->debugChannel === 'block-dist'){
										$user->sendMessage('dist=' . $distance);
									}
								}
							}
							if($inHand->getId() === ItemIds::BUCKET && $inHand->getDamage() === 8){
								$pos = $clickedBlockPos;
								$blockClicked = $user->player->getLevel()->getBlock($clickedBlockPos);
								// the block can't be replaced and the block relative to the face can also not be replaced
								// water-logging blocks by placing the water under the transparent block... idot stuff
								if(!$blockClicked->canBeReplaced() && !$user->player->getLevel()->getBlock($clickedBlockPos->getSide($packet->trData->getFace()))->canBeReplaced()){
									$pos = $clickedBlockPos->getSide($packet->trData->getFace());
								}
								$pk = new UpdateBlockPacket();
								$pk->x = $pos->x; $pk->y = $pos->y; $pk->z = $pos->z;
								$pk->blockRuntimeId = 134; $pk->flags = UpdateBlockPacket::FLAG_NETWORK;
								$pk->dataLayerId = UpdateBlockPacket::DATA_LAYER_LIQUID;
								foreach($user->player->getLevel()->getPlayers() as $v){
									$v->dataPacket($pk);
								}
								$user->player->dataPacket($pk);
							} elseif($block instanceof Transparent && $user->player->getLevel()->getBlock($clickedBlockPos->getSide($packet->trData->getFace()), false, false) instanceof Water){
								// reverse-waterlogging?
								$pk = new UpdateBlockPacket();
								$pos = $clickedBlockPos->getSide($packet->trData->getFace());
								$pk->x = $pos->x; $pk->y = $pos->y; $pk->z = $pos->z;
								$pk->dataLayerId = UpdateBlockPacket::DATA_LAYER_LIQUID;
								$pk->blockRuntimeId = 134; $pk->flags = UpdateBlockPacket::FLAG_NETWORK;
								foreach($user->player->getLevel()->getPlayers() as $v){
									$v->dataPacket($pk);
								}
								$user->player->dataPacket($pk);
							}
							// TODO: Fix water-logging with doors.. what the actual fuck?
							// ^ at this rate I might just not fix to be honest.
							break;
					}
				}
                // $user->testProcessor->process($packet);
                break;
            case LevelSoundEventPacket::NETWORK_ID:
                /** @var LevelSoundEventPacket $packet */
                switch($packet->sound){
                    case LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE:
                        $this->handleClick($user);
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
                    /* $pk = new NetworkStackLatencyPacket();
                    $pk->needResponse = true; $pk->timestamp = mt_rand(100000, 10000000) * 1000;
                    $user->latencyPacket = $pk; */
                    $user->latencyPacket->timestamp = mt_rand(1, 10000000) * 1000;
                    $user->latencyPacket->encode();
                } elseif($packet->timestamp === $user->chunkResponsePacket->timestamp){
                    $user->hasReceivedChunks = true;
                    if($user->debugChannel === 'receive-chunk'){
                        $user->sendMessage('received chunks');
                    }
                    $user->chunkResponsePacket->timestamp = mt_rand(10, 10000000) * 1000;
                    $user->chunkResponsePacket->encode();
                } elseif(isset($user->outboundProcessor->pendingMotions[$packet->timestamp])){
                    $motion = $user->outboundProcessor->pendingMotions[$packet->timestamp];
                    if($user->debugChannel === 'get-motion'){
                        $user->sendMessage('got ' . $packet->timestamp);
                    }
                    $user->timeSinceMotion = 0;
                    $user->moveData->lastMotion = $motion;
                    unset($user->outboundProcessor->pendingMotions[$packet->timestamp]);
                } elseif(isset($user->outboundProcessor->pendingLocations[$packet->timestamp])){
                    $location = $user->outboundProcessor->pendingLocations[$packet->timestamp];
                    $user->tickData->targetLocations[$user->tickData->currentTick] = $location;
                    $currentTick = $user->tickData->currentTick;
                    $user->tickData->targetLocations = array_filter($user->tickData->targetLocations, function(int $tick) use($currentTick) : bool{
                        return $currentTick - $tick <= 4;
                    }, ARRAY_FILTER_USE_KEY);
                    if($user->debugChannel === 'get-location'){
                        $user->sendMessage('got ' . $packet->timestamp);
                    }
                    unset($user->outboundProcessor->pendingLocations[$packet->timestamp]);
                } elseif(isset($user->ghostBlocks[$packet->timestamp])){
                    $block = $user->ghostBlocks[$packet->timestamp];
                    if($user->debugChannel === 'ghost-block'){
                        $user->sendMessage('ghost block ' . $block->getId() . ' removed with (x=' . $block->getX() . ' y=' . $block->getY() . ' z=' . $block->getZ() . ')');
                    }
                    unset($user->ghostBlocks[$packet->timestamp]);
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

    private function handleClick(User $user) : void{
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
        if($user->mouseRecorder !== null && $user->mouseRecorder->isRunning && $user->moveData->yawDelta > 0){
            $user->mouseRecorder->handleClick();
        }
    }

}