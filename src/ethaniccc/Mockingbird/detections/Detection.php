<?php

namespace ethaniccc\Mockingbird\detections;

use ethaniccc\Mockingbird\detections\movement\MovementDetection;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\BanTask;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

abstract class Detection{

    private $violations = [];
    protected $settings;
    protected $preVL, $maxVL;
    protected $vlThreshold = 2;
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
    }

    public function getSetting(string $setting){
        return $this->settings[$setting] ?? null;
    }

    public abstract function handle(DataPacket $packet, User $user) : void;

    public function getCheatProbability() : int{
        $lowMax = floor(pow($this->vlThreshold, 1 / 4) * 5);
        $mediumMax = floor(sqrt($this->vlThreshold) * 5);
        $violations = count($this->violations);
        if($violations <= $lowMax){
            return self::PROBABILITY_LOW;
        } elseif($violations <= $mediumMax){
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
        if($this instanceof MovementDetection && $this->suppression){
            if(!$user->onGround){
                $user->player->teleport($user->lastOnGroundLocation);
            } else {
                $user->player->teleport($user->lastLocation);
            }
        }
        if($this->punishable && $violations >= $this->maxVL){
            switch($this->punishType){
                case "kick":
                    $user->loggedIn = false;
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new KickTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 0);
                    break;
                case "ban":
                    $user->loggedIn = false;
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new BanTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 0);
                    break;
            }
            $message = $this->getPlugin()->getPrefix() . " " . str_replace("{player}", $name, $this->getPlugin()->getConfig()->get("punish_message_staff"));
            Server::getInstance()->broadcastMessage($message, $staff);
        }
        if($debugData !== null){
            $this->debug("{$user->player->getName()}: $debugData");
        }
    }

    protected function debug($debugData, bool $logWrite = true) : void{
        $debugData = (string) $debugData;
        $debugUsers = array_filter(Server::getInstance()->getOnlinePlayers(), function(Player $p) : bool{
            return $p->hasPermission("mockingbird.debug") && UserManager::getInstance()->get($p)->debug;
        });
        $debugMsg = $this->getPlugin()->getPrefix() . TextFormat::RED . " [DEBUG@{$this->name}] " . TextFormat::WHITE . $debugData;
        Server::getInstance()->broadcastMessage($debugMsg, $debugUsers);
        if($logWrite){
            Mockingbird::getInstance()->debugTask->addData($debugData);
        }
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