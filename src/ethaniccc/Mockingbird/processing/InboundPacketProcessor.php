<?php

namespace ethaniccc\Mockingbird\processing;

use ErrorException;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\MathUtils;
use Exception;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Cobweb;
use pocketmine\block\Ladder;
use pocketmine\block\Liquid;
use pocketmine\block\Vine;
use pocketmine\color\Color;
use pocketmine\entity\Attribute;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetLocalPlayerAsInitializedPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\DustParticle;

class InboundPacketProcessor extends Processor{

	/** @var Vector3[] */
	private array $postPendingTeleports = [];

	public function __construct(){
		$this->lastTime = microtime(true);
	}

	public function process(DataPacket $packet, User $user) : void{
		switch($packet->pid()){
			case PlayerAuthInputPacket::NETWORK_ID:
				if(!$packet instanceof PlayerAuthInputPacket){
					return;
				}
				if(!$user->loggedIn){
					return;
				}
				$location = Location::fromObject($packet->getPosition()->subtract(0, 1.62, 0), $user->player->getWorld(), $packet->getYaw(), $packet->getPitch());
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
					$user->moveData->moveDelta = $user->moveData->location->subtractVector($user->moveData->lastLocation)->asVector3();
					$user->moveData->lastYawDelta = $user->moveData->yawDelta;
					$user->moveData->lastPitchDelta = $user->moveData->pitchDelta;
					$user->moveData->yawDelta = abs($user->moveData->lastYaw - $user->moveData->yaw);
					$user->moveData->pitchDelta = abs($user->moveData->lastPitch - $user->moveData->pitch);
					$user->moveData->rotated = $user->moveData->yawDelta > 0 || $user->moveData->pitchDelta > 0;
					if($user->moveData->rotated && $user->debugChannel === 'rotation'){
						$user->sendMessage('yawDelta=' . $user->moveData->yawDelta . ' pitchDelta=' . $user->moveData->pitchDelta);
					}
				}else{
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
				}else{
					$user->timeSinceJoin = 0;
				}
				++$user->timeSinceMotion;
				if(!$user->player->isFlying()){
					++$user->timeSinceStoppedFlight;
				}else{
					$user->timeSinceStoppedFlight = 0;
				}
				if($user->player->isGliding() || $user->player->isSpectator() || $user->player->hasNoClientPredictions()){
					$user->timeSinceStoppedGlide = 0;
				}else{
					++$user->timeSinceStoppedGlide;
				}
				if($user->player->getEffects()->get(VanillaEffects::LEVITATION()) !== null
					// TODO: Slow falling is not implemented within PocketMine-MP.
					/*|| $user->player->getEffects()->get(VanillaEffects::SLOW_FALLING()) !== null*/){
					$user->moveData->levitationTicks = 0;
				}else{
					++$user->moveData->levitationTicks;
				}
				if($location->y > -39.5){
					++$user->moveData->ticksSinceInVoid;
				}else{
					$user->moveData->ticksSinceInVoid = 0;
				}
				// 0.03 ^ 2
				if($user->moveData->moveDelta->lengthSquared() > 0.0009){
					$speed = $user->player->getAttributeMap()->get(Attribute::MOVEMENT_SPEED)->getValue();
					if($user->debugChannel === 'speed'){
						$user->sendMessage('speed=' . $speed);
					}
					$liquids = 0;
					$cobweb = 0;
					$climb = 0;
					$inset = 0.001;
					$bb = $user->player->getBoundingBox();
					$minX = (int) floor($bb->minX + $inset);
					$minY = (int) floor($bb->minY + $inset);
					$minZ = (int) floor($bb->minZ + $inset);
					$maxX = (int) floor($bb->maxX - $inset);
					$maxY = (int) floor($bb->maxY - $inset);
					$maxZ = (int) floor($bb->maxZ - $inset);
					$blocks = [];
					for($z = $minZ; $z <= $maxZ; ++$z){
						for($x = $minX; $x <= $maxX; ++$x){
							for($y = $minY; $y <= $maxY; ++$y){
								$block = $user->player->getWorld()->getBlockAt($x, $y, $z);
								if($block->hasEntityCollision()){
									$blocks[] = $block;
								}
							}
						}
					}
					foreach($blocks as $block){
						if($block instanceof Liquid){
							$liquids++;
						}elseif($block instanceof Cobweb){
							$cobweb++;
						}elseif($block instanceof Ladder || $block instanceof Vine){
							$climb++;
						}
					}
					if($liquids > 0){
						$user->moveData->liquidTicks = 0;
					}else{
						++$user->moveData->liquidTicks;
					}
					if($cobweb > 0){
						$user->moveData->cobwebTicks = 0;
					}else{
						++$user->moveData->cobwebTicks;
					}
					if($climb > 0){
						$user->moveData->climbableTicks = 0;
					}else{
						++$user->moveData->climbableTicks;
					}
					// debug for block AABB - (VERY RESOURCE INTENSIVE)
					if($user->debugChannel === 'block-bb'){
						$org = $user->moveData->AABB;
						$pmmpAABB = AABB::toPMMPAABB($org);
						$expandedAABB = $pmmpAABB->expand(4, 4, 4);
						$distance = PHP_INT_MAX;
						$target = null;
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
									$block = $user->player->getWorld()->getBlockAt($x, $y, $z);
									if($block->getTypeId() !== BlockTypeIds::AIR){
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
								$user->player->getWorld()->addParticle($cornerVector, new DustParticle(new Color(0, 255, 255)));
							}
						}
					}
				}
				// 0.03 ^ 2
				if($user->moveData->moveDelta->lengthSquared() > 0.0009){
					// should I be worried about performance here?
					$org = $user->moveData->AABB;
					$pmmpAABB = AABB::toPMMPAABB($org);
					$verticalBlocks = $user->player->getWorld()->getCollisionBlocks($pmmpAABB->expandedCopy(0.1, 0.2, 0.1));
					$horizontalBlocks = $user->player->getWorld()->getCollisionBlocks($pmmpAABB->expandedCopy(0.2, -0.1, 0.2));
					$ghostCollisions = 0;
					$user->moveData->ghostCollisions = [];
					$verticalAABB = $pmmpAABB->expandedCopy(0.1, 0.2, 0.1);
					foreach($user->ghostBlocks as $block){
						if(!$block->hasEntityCollision() &&
							AABB::toPMMPAABB(AABB::fromBlock($block))->intersectsWith($verticalAABB, 0.0001)){
							$ghostCollisions++;
							$user->moveData->ghostCollisions[] = $block;
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
				}else{
					++$user->moveData->offGroundTicks;
					$user->moveData->onGroundTicks = 0;
				}
				if($hasMoved){
					$user->moveData->lastDirectionVector = $user->moveData->directionVector;
					try{
						$user->moveData->directionVector = MathUtils::directionVectorFromValues($user->moveData->yaw, $user->moveData->pitch);
					}catch(ErrorException $e){
						$user->moveData->directionVector = clone $user->zeroVector;
					}
				}
				$user->moveData->pressedKeys = [];
				if($packet->getMoveVecZ() > 0){
					$user->moveData->pressedKeys[] = 'W';
				}elseif($packet->getMoveVecZ() < 0){
					$user->moveData->pressedKeys[] = 'S';
				}
				if($packet->getMoveVecX() > 0){
					$user->moveData->pressedKeys[] = 'A';
				}elseif($packet->getMoveVecX() < 0){
					$user->moveData->pressedKeys[] = 'D';
				}
				$session = $user->player->getNetworkSession();
				$user->tickProcessor->process($packet, $user);
				++$this->tickSpeed;
				// $user->testProcessor->process($packet, $user);
				break;
			case InventoryTransactionPacket::NETWORK_ID:
				/** @var InventoryTransactionPacket $packet */
                $trData = $packet->trData;
				if ($trData->getTypeId() == InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                    if ($trData->getActionType() == UseItemOnEntityTransactionData::ACTION_ATTACK) {
                        /** @var UseItemOnEntityTransactionData $trData */
                        $user->hitData->attackPos = $trData->getPlayerPosition();
                        $user->hitData->lastTargetEntity = $user->hitData->targetEntity;
                        $user->hitData->targetEntity = $user->player->getWorld()->getEntity($trData->getActorRuntimeId());
                        $user->hitData->inCooldown = Server::getInstance()->getTick() - $user->hitData->lastTick < 10;
                        if (!$user->hitData->inCooldown) {
                            $user->timeSinceAttack = 0;
                            $user->hitData->lastTick = Server::getInstance()->getTick();
                        }
                        if ($user->hitData->targetEntity !== $user->hitData->lastTargetEntity) {
                            $user->tickData->targetLocations = [];
                            $user->outboundProcessor->pendingLocations = [];
                        }
                    }
                    $this->handleClick($user);
                }
				// $user->testProcessor->process($packet);
				break;
			case LevelSoundEventPacket::NETWORK_ID:
				/** @var LevelSoundEventPacket $packet */
				switch($packet->sound){
					case LevelSoundEvent::ATTACK_NODAMAGE:
						$this->handleClick($user);
						break;
				}
				break;
			case NetworkStackLatencyPacket::NETWORK_ID:
				/** @var NetworkStackLatencyPacket $packet */
				$timestamp = $packet->timestamp / ($user->player->getPlayerInfo()->getExtraData()["DeviceOS"] === DeviceOS::PLAYSTATION ? 1000 : 1000 * 1000);
				if($timestamp === $user->latencyPacket->timestamp){
					$user->responded = true;
					$user->transactionLatency = round((microtime(true) - $user->lastSentNetworkLatencyTime) * 1000, 0);
					if($user->debugChannel === 'latency'){
						$user->sendMessage("pmmp={$user->player->getNetworkSession()->getPing()} latency={$user->transactionLatency}");
					}
					/* $pk = new NetworkStackLatencyPacket();
					$pk->needResponse = true; $pk->timestamp = mt_rand(100000, 10000000) * 1000;
					$user->latencyPacket = $pk; */
					$user->latencyPacket->timestamp = mt_rand(1, 10000000) * 1000;
					$user->latencyPacket->encode(PacketSerializer::encoder());
				}elseif($timestamp === $user->chunkResponsePacket->timestamp){
					$user->hasReceivedChunks = true;
					if($user->debugChannel === 'receive-chunk'){
						$user->sendMessage('received chunks');
					}
					$user->chunkResponsePacket->timestamp = mt_rand(10, 10000000) * 1000;
					$user->chunkResponsePacket->encode(PacketSerializer::encoder());
				}elseif(isset($user->outboundProcessor->pendingMotions[$timestamp])){
					$motion = $user->outboundProcessor->pendingMotions[$timestamp];
					if($user->debugChannel === 'get-motion'){
						$user->sendMessage('got ' . $timestamp);
					}
					$user->timeSinceMotion = 0;
					$user->moveData->lastMotion = $motion;
					unset($user->outboundProcessor->pendingMotions[$timestamp]);
				}elseif(isset($user->outboundProcessor->pendingLocations[$timestamp])){
					$location = $user->outboundProcessor->pendingLocations[$timestamp];
					$user->tickData->targetLocations[$user->tickData->currentTick] = $location;
					$currentTick = $user->tickData->currentTick;
					$user->tickData->targetLocations = array_filter($user->tickData->targetLocations, function(int $tick) use ($currentTick) : bool{
						return $currentTick - $tick <= 4;
					}, ARRAY_FILTER_USE_KEY);
					if($user->debugChannel === 'get-location'){
						$user->sendMessage('got ' . $timestamp);
					}
					unset($user->outboundProcessor->pendingLocations[$timestamp]);
				}elseif(isset($user->ghostBlocks[$timestamp])){
					$block = $user->ghostBlocks[$timestamp];
					if($user->debugChannel === 'ghost-block'){
						$user->sendMessage('ghost block ' . $block->getTypeId() . ' removed with (x=' . $block->getPosition()->getX() . ' y=' . $block->getPosition()->getY() . ' z=' . $block->getPosition()->getZ() . ')');
					}
					unset($user->ghostBlocks[$timestamp]);
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
				}catch(Exception $e){
				}
				break;
			case SetLocalPlayerAsInitializedPacket::NETWORK_ID:
				$user->loggedIn = true;
				if($user->player->hasPermission('mockingbird.alerts') && Mockingbird::getInstance()->getConfig()->get('alerts_default')){
					$user->alerts = true;
				}
				$user->player->getNetworkSession()->sendDataPacket($user->latencyPacket);
				$user->lastSentNetworkLatencyTime = microtime(true);
				$user->responded = false;
				break;
		}
	}

	private array $clicks = [];
	private ?float $lastTime;
	private int $tickSpeed = 0;

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