<?php

namespace ethaniccc\Mockingbird\detections;

use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\BanTask;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\event\Event;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class Detection{

    private $violations = [];
    protected $settings;
    protected $vlThreshold = 2;
    protected $lowMax, $mediumMax;
    public $preVL, $maxVL;
    public $name, $subType, $enabled, $punishable, $punishType, $suppression, $alerts;

    public const PROBABILITY_LOW = 1;
    public const PROBABILITY_MEDIUM = 2;
    public const PROBABILITY_HIGH = 3;

    public function __construct(string $name, ?array $settings){
        $this->name = $name;
        $this->subType = substr($this->name, -1);
        $this->settings = $settings === null ? ["enabled" => true, "punish" => false] : $settings;
        $this->enabled = $this->settings["enabled"];
        $this->punishable = $this->settings["punish"];
        $this->punishType = $this->settings["punishment_type"] ?? "kick";
        $this->suppression = $this->settings["suppression"] ?? false;
        $this->maxVL = $this->settings["max_violations"] ?? 25;
        $this->alerts = Mockingbird::getInstance()->getConfig()->get("alerts_enabled") ?? true;
        $this->lowMax = floor(pow($this->vlThreshold, 1 / 4) * 5);
        $this->mediumMax = floor(sqrt($this->vlThreshold) * 5);
    }

    public function getSetting(string $setting){
        return $this->settings[$setting] ?? null;
    }

    public abstract function handle(DataPacket $packet, User $user) : void;

    public function handleEvent(Event $event, User $user) : void{
    }

    public function getCheatProbability() : int{
        $violations = count($this->violations);
        if($violations <= $this->lowMax){
            return self::PROBABILITY_LOW;
        } elseif($violations <= $this->mediumMax){
            return self::PROBABILITY_MEDIUM;
        } else {
            return self::PROBABILITY_HIGH;
        }
    }

    public function probabilityColor(int $probability) : string{
        switch($probability){
            case self::PROBABILITY_LOW:
                return TextFormat::GREEN . "Low";
            case self::PROBABILITY_MEDIUM:
                return TextFormat::GOLD . "Medium";
            case self::PROBABILITY_HIGH:
                return TextFormat::RED . "High";
        }
        return "";
    }

    protected function fail(User $user, ?string $debugData = null) : void{
        if(!$user->loggedIn){
            return;
        }
        if(!isset($user->violations[$this->name])){
            $user->violations[$this->name] = 0;
        }
        ++$user->violations[$this->name];
        $this->violations[] = microtime(true);
        $this->violations = array_filter($this->violations, function(float $lastTime) : bool{
            return microtime(true) - $lastTime <= $this->vlThreshold * (20 / Server::getInstance()->getTicksPerSecond());
        });
        $name = $user->player->getName();
        $cheatName = $this->name;
        $violations = round($user->violations[$this->name], 0);
        $staff = array_filter(Server::getInstance()->getOnlinePlayers(), function(Player $p) : bool{
            return $p->hasPermission("mockingbird.alerts") && UserManager::getInstance()->get($p)->alerts;
        });
        if($this->alerts){
            $message = $this->getPlugin()->getPrefix() . " " . str_replace(["{player}", "{check}", "{vl}", "{probability}"], [$name, $cheatName, $violations, $this->probabilityColor($this->getCheatProbability())], $this->getPlugin()->getConfig()->get("fail_message"));
            Server::getInstance()->broadcastMessage($message, $staff);
        }
        if($this instanceof CancellableMovement && $this->suppression){
            if(!$user->moveData->onGround){
                $user->player->teleport($user->moveData->lastOnGroundLocation);
            } else {
                $user->player->teleport($user->moveData->lastLocation);
            }
        }
        if($this->punishable && $violations >= $this->maxVL){
            switch($this->punishType){
                case "kick":
                    $user->loggedIn = false;
                    $this->debug("{$user->player->getName()} was punished for {$this->name}");
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new KickTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 0);
                    break;
                case "ban":
                    $user->loggedIn = false;
                    $this->debug("{$user->player->getName()} was punished for {$this->name}");
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new BanTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 0);
                    break;
            }
            $message = $this->getPlugin()->getPrefix() . " " . str_replace(["{player}", "{detection}"], [$name, $this->name], $this->getPlugin()->getConfig()->get("punish_message_staff"));
            Server::getInstance()->broadcastMessage($message, Mockingbird::getInstance()->getConfig()->get("punish_message_global") ? Server::getInstance()->getOnlinePlayers() : $staff);
        }
        if($debugData !== null){
            if(!isset($user->debugCache[strtolower($this->name)])){
                $user->debugCache[strtolower($this->name)] = '';
            }
            $user->debugCache[strtolower($this->name)] .= $debugData . PHP_EOL;
            $this->debug("{$user->player->getName()}: $debugData");
        }
    }

    protected function debug($debugData, bool $logWrite = true) : void{
        if($logWrite){
            Mockingbird::getInstance()->debugTask->addData($debugData);
        }
    }

    protected function isDebug(User $user) : bool{
        return strtolower($user->debugChannel) === strtolower($this->name);
    }

    protected function reward(User $user, float $multiplier) : void{
        if(isset($user->violations[$this->name])){
            $user->violations[$this->name] *= $multiplier;
        }
    }

    protected function getPlugin() : Mockingbird{
        return Mockingbird::getInstance();
    }

}