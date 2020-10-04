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

    protected $settings;
    protected $preVL, $maxVL;
    public $name, $subType, $enabled, $punishable, $punishType, $suppression;

    public function __construct(string $name, ?array $settings){
        $this->name = $name;
        $this->subType = substr($this->name, -1);
        $this->settings = $settings === null ? ["enabled" => true, "punishable" => false] : $settings;
        $this->enabled = $this->settings["enabled"] ?? false;
        $this->punishable = $this->settings["punish"] ?? false;
        $this->punishType = $this->settings["punishment_type"] ?? "kick";
        $this->suppression = $this->settings["suppression"] ?? false;
        $this->maxVL = $this->settings["max_violations"] ?? 25;
    }

    public function getSetting(string $setting){
        return $this->settings[$setting] ?? null;
    }

    public abstract function process(DataPacket $packet, User $user) : void;

    protected function fail(User $user, ?string $debugData = null) : void{
        if(!$user->loggedIn){
            return;
        }
        if(!isset($user->violations[$this->name])){
            $user->violations[$this->name] = 0;
        }
        ++$user->violations[$this->name];
        $name = $user->player->getName();
        $cheatName = $this->name;
        $violations = round($user->violations[$this->name], 0);
        $message = $this->getPlugin()->getPrefix() . " " . str_replace(["{player}", "{check}", "{vl}"], [$name, substr_replace($cheatName, "({$this->subType})", -1), $violations], $this->getPlugin()->getConfig()->get("fail_message"));
        $staff = array_filter(Server::getInstance()->getOnlinePlayers(), function(Player $p) : bool{
           return $p->hasPermission("mockingbird.alerts") && UserManager::getInstance()->get($p)->alerts;
        });
        Server::getInstance()->broadcastMessage($message, $staff);
        if($this instanceof MovementDetection && $this->suppression){
            if(!$user->serverOnGround){
                $user->player->teleport($user->lastOnGroundLocation);
            } else {
                $user->player->teleport($user->lastLocation);
            }
        }
        if($violations >= $this->maxVL){
            switch($this->punishType){
                case "kick":
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new KickTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 1);
                    break;
                case "ban":
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new BanTask($user, $this->getPlugin()->getPrefix() . " " . $this->getPlugin()->getConfig()->get("punish_message_player")), 1);
                    break;
            }
            $message = $this->getPlugin()->getPrefix() . " " . str_replace("{player}", $name, $this->getPlugin()->getConfig()->get("punish_message_staff"));
            Server::getInstance()->broadcastMessage($message, $staff);
        }
        if($debugData === null){
            return;
        }
        $debugUsers = array_filter(Server::getInstance()->getOnlinePlayers(), function(Player $p) : bool{
            return $p->hasPermission("mockingbird.debug") && UserManager::getInstance()->get($p)->debug;
        });
        $debugMsg = $this->getPlugin()->getPrefix() . TextFormat::RED . " [DEBUG@{$this->name}] " . TextFormat::WHITE . $debugData;
        Server::getInstance()->broadcastMessage($debugMsg, $debugUsers);
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