<?php

namespace ethaniccc\Mockingbird\utils\user;

use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use ethaniccc\Mockingbird\utils\location\Vector4;
use pocketmine\block\Air;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;

class User{

    private $player;
    private $isMobile;
    private $timeSinceJoin = 0;

    private $currentLocation, $lastLocation;
    private $moveDelta, $lastMoveDelta;
    private $moveDistance, $lastMoveDistance;
    private $locationHistory;
    private $clientOnGround, $serverOnGround = true;
    private $currentYaw, $currentPitch, $previousYaw, $previousPitch = 0;
    private $offGroundTicks = 0;
    private $timeSinceJump = 0;
    private $timeSinceTeleport = 0;

    private $timeSinceHit = 0;
    private $damagedTick = 0;

    private $lastHitEntity;
    private $timeSinceAttack = 0;
	private $clientData;

	private $timeSinceMotion = 0;
	private $currentMotion, $lastMotion;

	private $attackPosition;

    public function __construct(Player $player, bool $isMobile, LoginPacket $packet){
        $this->player = $player;
        $this->isMobile = $isMobile;
        $this->locationHistory = new LocationHistory($this);
        $this->lastMoveDelta = new Vector3(0, 0, 0);
        $this->moveDelta = new Vector3(0, 0, 0);
        $this->clientData = new ClientData($packet->clientData);
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getName() : string{
        return $this->player->getName();
    }

    public function getClientData() : ClientData{
        return $this->clientData;
    }

    public function handleMove(MoveEvent $event) : void{
        $this->lastMoveDelta = $this->moveDelta;
        $this->moveDelta = new Vector3($event->getDistanceX(), $event->getDistanceY(), $event->getDistanceZ());
        $this->locationHistory->addLocation(new Vector4($event->getTo()->x, $event->getTo()->y, $event->getTo()->z));
        $this->lastLocation = $this->currentLocation;
        // jesus christ mojang lmao why are the move packets like dis
        $this->currentLocation = $event->getTo()->round(4)->subtract(0, 1.62, 0);
        $this->clientOnGround = $event->onGround();
        $this->previousYaw = $this->currentYaw;
        $this->currentYaw = $event->getYaw();
        $this->previousPitch = $this->currentPitch;
        $this->currentPitch = $event->getPitch();
        $this->lastMoveDistance = $this->moveDistance;
        $this->moveDistance = $event->getDistanceXZ();
        $this->serverOnGround = LevelUtils::isNearGround($this);
        // off ground ticks will be done with server side information.
        $this->serverOnGround ? $this->offGroundTicks = 0 : ++$this->offGroundTicks;
        if($event->getMode() === MoveEvent::MODE_TELEPORT){
            $this->timeSinceTeleport = 0;
        } else {
            ++$this->timeSinceTeleport;
        }
        ++$this->timeSinceJump;
        ++$this->timeSinceMotion;
        ++$this->timeSinceJoin;
        ++$this->timeSinceHit;
        ++$this->timeSinceAttack;
    }

    public function getMoveDistance() : ?float{
        return $this->moveDistance;
    }

    public function getLastMoveDistance() : ?float{
        return $this->lastMoveDistance;
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
        return (float) $this->currentYaw;
    }

    public function getCurrentPitch() : float{
        return (float) $this->currentPitch;
    }

    public function getPreviousYaw() : float{
        return (float) $this->previousYaw;
    }

    public function getPreviousPitch() : float{
        return (float) $this->previousPitch;
    }

    public function timePassedSinceTeleport(int $tickDiff) : bool{
        return $this->timeSinceTeleport >= $tickDiff;
    }

    public function hasNoMotion() : bool{
        return (new Vector3(0, 0, 0))->distance($this->player->getMotion()) == 0;
    }

    public function isMobile() : bool{
        return $this->clientData->isMobile();
    }

    public function handleHit(EntityDamageByEntityEvent $event) : void{
        if(spl_object_hash($event->getDamager()) == spl_object_hash($this->player)){
            $this->lastHitEntity = $event->getEntity();
            $this->timeSinceAttack = 0;
        } else {
            $this->timeSinceHit = 0;
        }
    }

    public function getLastAttackedEntity() : ?Entity{
        return $this->lastHitEntity;
    }

    // not used anywhere for now
    public function timePassedSinceAttack(int $tickDiff) : bool{
        return $this->timeSinceAttack >= $tickDiff;
    }

    public function timePassedSinceHit(int $tickDiff) : bool{
        return $this->timeSinceHit >= $tickDiff;
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
        $this->timeSinceJoin = 0;
    }

    public function timePassedSinceJoin(int $tickDiff) : bool{
        return $this->timeSinceJoin >= $tickDiff;
    }

    public function handleJump(PlayerJumpEvent $event) : void{
        $this->timeSinceJump = 0;
    }

    public function timePassedSinceJump(int $tickDiff) : bool{
        return $this->timeSinceJump >= $tickDiff;
    }

    public function handleMotion(EntityMotionEvent $event) : void{
        $this->lastMotion = $this->currentMotion;
        $this->currentMotion = $event->getVector();
        $this->timeSinceMotion = 0;
    }

    public function getCurrentMotion() : ?Vector3{
        return $this->currentMotion;
    }

    public function getLastMotion() : ?Vector3{
        return $this->lastMotion;
    }

    public function timePassedSinceMotion(int $tickDiff) : bool{
        return $this->timeSinceMotion >= $tickDiff;
    }

    public function setAttackPosition(Vector3 $position) : void{
        $this->attackPosition = $position;
    }

    public function getAttackPosition() : ?Vector3{
        return $this->attackPosition;
    }

}