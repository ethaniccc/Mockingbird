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
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
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
                unset($user->moveData->AABB);
                $user->moveData->AABB = AABB::from($user);
                $user->moveData->lastYaw = $user->moveData->yaw;
                $user->moveData->lastPitch = $user->moveData->pitch;
                $user->moveData->yaw = fmod($location->yaw, 360);
                $user->moveData->pitch = fmod($location->pitch, 360);
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
                if(!$user->player->getGenericFlag(Player::DATA_FLAG_GLIDING)){
                    ++$user->timeSinceStoppedGlide;
                } else {
                    $user->timeSinceStoppedGlide = 0;
                }
                if($location->y > -39.5){
                    ++$user->moveData->ticksSinceInVoid;
                } else {
                    $user->moveData->ticksSinceInVoid = 0;
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
                if($movePacket->onGround){
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
                    if(!$user->moveData->moveDelta->equals($user->zeroVector) || $user->moveData->yawDelta > 0 || $user->moveData->pitch > 0){
                        // only handle if the move delta is greater than 0 so PlayerMoveEvent isn't spammed
                        $user->player->handleMovePlayer($movePacket);
                    }
                }
                $user->tickProcessor->process($packet);
                ++$this->tickSpeed;
                break;
            case InventoryTransactionPacket::NETWORK_ID:
                /** @var InventoryTransactionPacket $packet */
                switch($packet->transactionType){
                    case InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY:
                        switch($packet->trData->actionType){
                            case InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK:
                                $user->hitData->attackPos = $packet->trData->playerPos;
                                $user->hitData->targetEntity = $user->player->getLevel()->getEntity($packet->trData->entityRuntimeId);
                                $user->hitData->inCooldown = Server::getInstance()->getTick() - $user->hitData->lastTick < 10;
                                if(!$user->hitData->inCooldown){
                                    $user->timeSinceAttack = 0;
                                    $user->hitData->lastTick = Server::getInstance()->getTick();
                                }
                                break;
                        }
                        $this->handleClick();
                        break;
                }
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
                if($packet->timestamp === $user->networkStackLatencyPacket->timestamp){
                    $user->responded = true;
                    $user->transactionLatency = round((microtime(true) - $user->lastSentNetworkLatencyTime) * 1000, 0);
                    if($user->debugChannel === "latency"){
                        $user->sendMessage("pmmp={$user->player->getPing()} latency={$user->transactionLatency}");
                    }
                }
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
                }
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