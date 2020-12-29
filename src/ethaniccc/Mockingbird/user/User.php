<?php

namespace ethaniccc\Mockingbird\user;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\processing\EventProcessor;
use ethaniccc\Mockingbird\processing\InboundPacketProcessor;
use ethaniccc\Mockingbird\processing\TickProcessor;
use ethaniccc\Mockingbird\threads\CalculationThread;
use ethaniccc\Mockingbird\user\data\ClickData;
use ethaniccc\Mockingbird\user\data\HitData;
use ethaniccc\Mockingbird\user\data\MoveData;
use ethaniccc\Mockingbird\user\data\TickData;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\block\Air;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\TextFormat;

class User{

    /** @var Player - The player associated with this User class. */
    public $player;
    /** @var InboundPacketProcessor - The processor that will handle all incoming packets */
    public $inboundProcessor;
    /** @var EventProcessor - The processor that will handle events related to the user. */
    public $eventProcessor;
    /** @var TickProcessor - The processor that will run every tick for particular data. */
    public $tickProcessor;
    /** @var Detection[] - The detections available that will run. */
    public $detections = [];
    /** @var array - The key is the detection name, and the value is the violations (float). - Make this a class? */
    public $violations = [];
    /** @var string[] - The key is the detection name and the value is a mini-debug log string. */
    public $debugCache = [];
    /** @var bool - The boolean value for if the user is logged into the server. */
    public $loggedIn = false;
    /** @var bool */
    public $isDesktop = false;
    /** @var bool - Boolean value for if the user is on Windows 10 */
    public $win10 = false;

    /** @var bool - The boolean value for if the user has alerts enabled. */
    public $alerts = false;
    /** @var string|null - The detection that the user should get debug information from. */
    public $debugChannel = null;

    /**
     * @var int - The client ticks that have passed since the specified "thing". For
     * instance, if the client sends 10 PlayerAuthInputPackets since their teleport,
     * $timeSinceTeleport would be 10.
     */
    public $timeSinceTeleport = 0;
    public $timeSinceJoin = 0;
    public $timeSinceMotion = 0;
    public $timeSinceDamage = 0;
    public $timeSinceAttack = 0;
    public $timeSinceStoppedFlight = 0;
    public $timeSinceLastBlockPlace = 0;
    public $timeSinceStoppedGlide = 0;

    /** @var int|float - The time the last NetworkStackLatencyPacket has been sent. */
    public $lastSentNetworkLatencyTime = 0;
    /** @var int|float - The time it took for the client to respond with a NetworkStackLatencyPacket. */
    public $transactionLatency = 0;
    /** @var bool - Boolean value for if the user responded with a NetworkStackLatencyPacket. */
    public $responded = false;

    /** @var Vector3 - Just a Vector3 with it's x, y, and z values at 0 - don't mind me! */
    public $zeroVector;
    /** @var NetworkStackLatencyPacket - So I don't have to create multiple of these. */
    public $networkStackLatencyPacket;

    /**
     * NOTE: I use these values (isSneaking, isSprinting, etc.) because Pocketmine's values will be off by at least one tick, since it has not
     * handled the packets to set these values yet.
     */

    /** @var bool - The boolean value for if the user is sneaking or not. */
    public $isSneaking = false;
    /** @var bool - The boolean value for if the user is sprinting or not. */
    public $isSprinting = false;

    /** @var MoveData - The class that stores the movement data of the user, the MoveProcessor will handle data to be put in here. */
    public $moveData;
    /** @var ClickData - The class that stores the click data of the user, the ClickProcessor will handle data to be put in here. */
    public $clickData;
    /** @var HitData - The class that stores the hit data of the user, the HitProcessor will handle data to be put in here. */
    public $hitData;
    /** @var TickData - The class that stores data updated every server tick. This data includes entity location history. */
    public $tickData;

    /** @var CalculationThread - The thread where decently sized calculations should be ran. */
    public $calculationThread;
    /** @var SleeperNotifier - The notifier of the calculation thread. */
    public $threadNotifier;

    public function __construct(Player $player){
        $this->player = $player;
        $this->moveData = new MoveData();
        $this->moveData->blockBelow = new Air();
        $this->moveData->blockAbove = new Air();
        $this->clickData = new ClickData();
        $this->hitData = new HitData();
        $this->tickData = new TickData();
        $this->moveData->lastOnGroundLocation = $player->asLocation();
        $zeroVector = new Vector3(0, 0, 0);
        $this->moveData->AABB = AABB::fromPosition($zeroVector);
        $this->zeroVector = $zeroVector;
        $this->moveData->moveDelta = $zeroVector;
        $this->moveData->lastMoveDelta = $zeroVector;
        $this->moveData->location = $player->asLocation();
        $this->moveData->lastLocation = $this->moveData->location;
        $this->moveData->lastMotion = $zeroVector;
        $this->moveData->directionVector = $zeroVector;
        $this->inboundProcessor = new InboundPacketProcessor($this);
        $this->eventProcessor = new EventProcessor($this);
        $this->tickProcessor = new TickProcessor($this);
        foreach(Mockingbird::getInstance()->availableChecks as $check){
            $this->detections[$check->name] = clone $check;
        }
        $this->networkStackLatencyPacket = new NetworkStackLatencyPacket();
        $this->networkStackLatencyPacket->needResponse = true;
        $this->networkStackLatencyPacket->timestamp = mt_rand(1, 100) * 1000;
        $this->threadNotifier = new SleeperNotifier();
        $this->calculationThread = new CalculationThread($this->threadNotifier);
        Server::getInstance()->getTickSleeper()->addNotifier($this->threadNotifier, function() : void{
            ($this->calculationThread->getFinishTask())($this->calculationThread->getFromDone());
        });
        $this->calculationThread->start(PTHREADS_INHERIT_NONE);
    }

    public function sendMessage(string $message) : void{
        $this->player->sendMessage(TextFormat::BOLD . TextFormat::DARK_GRAY . "[" . TextFormat::RED . "DEBUG" . TextFormat::DARK_GRAY . "]" . TextFormat::RESET . " $message");
    }

    public function __destruct(){
        $this->calculationThread->quit();
        Server::getInstance()->getTickSleeper()->removeNotifier($this->threadNotifier);
    }

}