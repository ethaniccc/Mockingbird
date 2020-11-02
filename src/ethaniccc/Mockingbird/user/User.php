<?php

namespace ethaniccc\Mockingbird\user;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use ReflectionClass;

class User{

    /** @var Player */
    public $player;
    public $processors, $checks = [];
    public $violations = [];
    public $loggedIn = false;
    public $isDesktop = false;

    public $alerts = false;
    public $debug = false;

    /** @var LocationHistory */
    public $locationHistory;
    /** @var Location|Vector3 */
    public $location, $lastLocation;
    /** @var Vector3 */
    public $moveDelta, $lastMoveDelta;
    /** @var bool */
    public $onGround = false;
    /** @var int */
    public $onGroundTicks = 0, $offGroundTicks = 0;
    /** @var Location */
    public $lastOnGroundLocation;
    /** @var null|Block */
    public $blockAbove, $blockBelow;
    /** @var Vector3 */
    public $currentMotion;
    /** @var float */
    public $yaw = 0, $pitch = 0, $lastYaw = 0, $lastPitch = 0;
    public $yawDelta = 0, $pitchDelta = 0, $lastYawDelta = 0, $lastPitchDelta = 0;
    /** @var bool */
    public $rotated = false;

    public $cps, $clickTime;

    /** @var string[] - The WASD combo the player is using */
    public $pressedKeys = [];

    // attack pos is the position of the damager when attacking the targetEntity given in the InventoryTransactionPacket
    public $attackPos;
    /** @var Entity */
    public $targetEntity;

    public $timeSinceTeleport = 0;
    public $timeSinceJoin = 0;
    public $timeSinceMotion = 0;
    public $timeSinceDamage = 0;
    public $timeSinceAttack = 0;

    public $lastSentNetworkLatencyTime = 0;
    public $transactionLatency = 0;

    public function __construct(Player $player){
        $this->player = $player;
        $this->locationHistory = new LocationHistory();
        $this->lastOnGroundLocation = $player->asLocation();
        $zeroVector = new Vector3(0, 0, 0);
        $this->moveDelta = $zeroVector;
        $this->lastMoveDelta = $zeroVector;
        $this->location = $zeroVector;
        $this->lastLocation = $this->location;
        foreach(Mockingbird::getInstance()->availableProcessors as $processorInfo){
            if($processorInfo instanceof ReflectionClass){
                $this->processors[] = $processorInfo->newInstanceArgs([$this]);
            }
        }
        foreach(Mockingbird::getInstance()->availableChecks as $checkInfo){
            if($checkInfo instanceof ReflectionClass){
                $this->checks[] = $checkInfo->newInstanceArgs([$checkInfo->getShortName(), Mockingbird::getInstance()->getConfig()->getNested($checkInfo->getShortName())]);
            }
        }
    }

}