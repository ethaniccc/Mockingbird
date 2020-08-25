<?php

namespace ethaniccc\Mockingbird\utils\user;

use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use ethaniccc\Mockingbird\utils\location\Vector4;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;

class User{

    private $player;
    private $isMobile;
    private $joinTick = 0;

    private $currentLocation, $lastLocation;
    private $moveDelta, $lastMoveDelta;
    private $locationHistory;
    private $clientOnGround, $serverOnGround = true;
    private $currentYaw, $currentPitch, $previousYaw, $previousPitch = 0;
    private $offGroundTicks = 0;
    private $lastJumpedTick = 0;

    private $lastHitTick = 0;
    private $damagedTick = 0;

    private $lastHitEntity;
    private $lastAttackedTick = 0;
    /** @var ClientData */
	private $clientData;
	/** @var LoginPacket */
	private $packet;

    public function __construct(Player $player, bool $isMobile, LoginPacket $packet){
        $this->player = $player;
        $this->isMobile = $isMobile;
        $this->locationHistory = new LocationHistory($player);
        $this->lastMoveDelta = new Vector3(0, 0, 0);
        $this->moveDelta = new Vector3(0, 0, 0);
        $this->clientData = new ClientData($packet->clientData);
        $this->packet = $packet;
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getName() : string{
        return $this->player->getName();
    }

    public function handleMove(MoveEvent $event) : void{
        $this->lastMoveDelta = $this->moveDelta;
        $this->moveDelta = new Vector3($event->getDistanceX(), $event->getDistanceY(), $event->getDistanceZ());
        $this->locationHistory->addLocation(new Vector4($event->getTo()->x, $event->getTo()->y, $event->getTo()->z));
        $this->lastLocation = $this->currentLocation;
        $this->currentLocation = $event->getTo();
        $this->clientOnGround = $event->onGround();
        $this->previousYaw = $this->currentYaw;
        $this->currentYaw = $event->getYaw();
        $this->previousPitch = $this->currentPitch;
        $this->currentPitch = $event->getPitch();
        $this->serverOnGround = LevelUtils::isNearGround($this->player);
        // off ground ticks will be done with server side information.
        $this->serverOnGround ? $this->offGroundTicks = 0 : ++$this->offGroundTicks;
    }

    public function getOffGroundTicks() : int{
        return $this->offGroundTicks;
    }

    public function getClientOnGround() : bool{
        return $this->clientOnGround;
    }

    public function getServerOnGround() : bool{
        return $this->serverOnGround;
    }

    public function getCurrentLocation() : ?Vector3{
        return $this->currentLocation;
    }

    public function getLastLocation() : ?Vector3{
        return $this->lastLocation;
    }

    public function getMoveDelta() : ?Vector3{
        return $this->moveDelta;
    }

    public function getLastMoveDelta() : ?Vector3{
        return $this->lastMoveDelta;
    }

    public function getLocationHistory() : LocationHistory{
        return $this->locationHistory;
    }

    public function getCurrentYaw() : float{
        return $this->currentYaw;
    }

    public function getCurrentPitch() : float{
        return $this->currentPitch;
    }

    public function getPreviousYaw() : float{
        return $this->previousYaw;
    }

    public function getPreviousPitch() : float{
        return $this->previousPitch;
    }

    public function hasNoMotion() : bool{
        return (new Vector3(0, 0, 0))->distance($this->player->getMotion()) == 0;
    }

    public function isMobile() : bool{
        return $this->isMobile;
    }

    public function handleHit(EntityDamageByEntityEvent $event) : void{
        if(spl_object_hash($event->getDamager()) === spl_object_hash($this->player)){
            $this->lastHitEntity = $event->getEntity();
            $this->lastAttackedTick = $this->player->getServer()->getTick();
        } else {
            $this->lastHitTick = $this->player->getServer()->getTick();
        }
    }

    public function getLastAttackedEntity() : ?Entity{
        return $this->lastHitEntity;
    }

    public function timePassedSinceAttack(int $tickDiff) : bool{
        return $this->player->getServer()->getTick() - $this->lastAttackedTick >= $tickDiff;
    }

    public function timePassedSinceHit(int $tickDiff) : bool{
        return $this->player->getServer()->getTick() - $this->lastHitTick >= $tickDiff;
    }

    public function handleDamage(EntityDamageEvent $event) : void{
        if(!$event instanceof EntityDamageByEntityEvent){
            $this->damagedTick = $this->player->getServer()->getTick();
        }
    }

    public function timePassedSinceDamage(int $tickDiff) : bool{
        return $this->player->getServer()->getTick() - $this->damagedTick >= $tickDiff;
    }

    public function handleJoin(PlayerJoinEvent $event) : void{
        $this->joinTick = $this->player->getServer()->getTick();
    }

    public function timePassedSinceJoin(int $tickDiff) : bool{
        return $this->player->getServer()->getTick() - $this->joinTick >= $tickDiff;
    }

    public function handleJump(PlayerJumpEvent $event) : void{
        $this->lastJumpedTick = $this->player->getServer()->getTick();
    }

    public function ticksPassedSinceJump(int $tickDiff) : bool{
        return $this->player->getServer()->getTick() - $this->lastJumpedTick >= $tickDiff;
    }

}