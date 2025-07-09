<?php

namespace ethaniccc\Mockingbird\user;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\processing\InboundPacketProcessor;
use ethaniccc\Mockingbird\processing\OutboundProcessor;
use ethaniccc\Mockingbird\processing\TestProcessor;
use ethaniccc\Mockingbird\processing\TickProcessor;
use ethaniccc\Mockingbird\user\data\ClickData;
use ethaniccc\Mockingbird\user\data\HitData;
use ethaniccc\Mockingbird\user\data\MoveData;
use ethaniccc\Mockingbird\user\data\TickData;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MouseRecorder;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class User{

	public Player $player;
	public InboundPacketProcessor $inboundProcessor;
	public OutboundProcessor $outboundProcessor;
	public TickProcessor $tickProcessor;
	public TestProcessor $testProcessor;
	public ?MouseRecorder $mouseRecorder = null;
	/** @var Detection[] */
	public array $detections = [];
	/** @var array<string, float> */
	public array $violations = [];
	/** @var array<string, string> */
	public array $debugCache = [];
	public bool $loggedIn = false;
	public bool $isDesktop = false;
	public bool $win10 = false;
	public bool $alerts = false;
	public int $alertCooldown;
	public ?string $debugChannel = null;
	public bool $isPacketLogged = false;
	/** @var DataPacket[] */
	public array $packetLog = [];
	public int $timeSinceTeleport = 0;
	public int $timeSinceJoin = 0;
	public int $timeSinceMotion = 0;
	public int $timeSinceDamage = 0;
	public int $timeSinceAttack = 0;
	public int $timeSinceStoppedFlight = 0;
	public int $timeSinceStoppedGlide = 0;
	/** @var Block[] */
	public array $placedBlocks = [];
	/** @var Block[] */
	public array $ghostBlocks = [];
	public int|float $lastSentNetworkLatencyTime = 0;
	public int|float $transactionLatency = 0;
	public bool $responded = false;
	public bool $hasReceivedChunks = false;
	public Vector3 $zeroVector;
	public NetworkStackLatencyPacket $latencyPacket;
	public NetworkStackLatencyPacket $chunkResponsePacket;
	public bool $isSneaking = false;
	public bool $isSprinting = false;
	public bool $isGliding = false;
	public MoveData $moveData;
	public ClickData $clickData;
	public HitData $hitData;
	public TickData $tickData;

	public function __construct(Player $player){
		$this->player = $player;
		$this->alertCooldown = ($cooldown = Mockingbird::getInstance()->getConfig()->get('default_alert_delay')) === false ? 2 : $cooldown;
		$this->moveData = new MoveData();
		$this->clickData = new ClickData();
		$this->hitData = new HitData();
		$this->hitData->lastTick = Server::getInstance()->getTick();
		$this->tickData = new TickData();
		$this->moveData->lastOnGroundLocation = $player->getLocation();
		$zeroVector = new Vector3(0, 0, 0);
		$this->moveData->AABB = AABB::fromPosition($zeroVector);
		$this->zeroVector = $zeroVector;
		$this->moveData->moveDelta = $zeroVector;
		$this->moveData->lastMoveDelta = $zeroVector;
		$this->moveData->location = $player->getLocation();
		$this->moveData->lastLocation = $this->moveData->location;
		$this->moveData->lastMotion = $zeroVector;
		$this->moveData->directionVector = $zeroVector;
		$this->inboundProcessor = new InboundPacketProcessor();
		$this->outboundProcessor = new OutboundProcessor();
		$this->tickProcessor = new TickProcessor();
		$this->testProcessor = new TestProcessor();
		foreach(Mockingbird::getInstance()->availableChecks as $check){
			$this->detections[$check->name] = clone $check;
		}
		$this->latencyPacket = new NetworkStackLatencyPacket();
		$this->latencyPacket->needResponse = true;
		$this->latencyPacket->timestamp = mt_rand(10, 1000000) * 1000;
		$this->chunkResponsePacket = new NetworkStackLatencyPacket();
		$this->chunkResponsePacket->needResponse = true;
		// to ensure that the two timestamps are NOT the same in any case (the chance of it happening is low, but still possible)
		$this->chunkResponsePacket->timestamp = $this->latencyPacket->timestamp + mt_rand(-10000, 10000) * 1000;
	}

	public function sendMessage(string $message) : void{
		if(!$this->loggedIn) return;
		$this->player->sendMessage(TextFormat::BOLD . TextFormat::DARK_GRAY . '[' . TextFormat::RED . 'DEBUG' . TextFormat::DARK_GRAY . ']' . TextFormat::RESET . ' ' . $message);
	}
}