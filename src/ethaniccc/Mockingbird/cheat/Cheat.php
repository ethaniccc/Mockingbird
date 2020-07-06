<?php

/*


$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\ 
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |                                  
                                                         \$$$$$$  |                                  
                                                          \______/      
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc                             
*/ 

namespace ethaniccc\Mockingbird\cheat;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\utils\TextFormat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;

class Cheat implements Listener{

    private $cheatName;
    private $cheatType;
    private $enabled;
    private $notifyCooldown = [];
    private $previousViolationTime = [];

    private $plugin;

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        $this->cheatName = $cheatName;
        $this->cheatType = $cheatType;
        $this->enabled = $enabled;
        $this->plugin = $plugin;
    }

    public function getName() : string{
        return $this->cheatName;
    }

    public function getType() : string{
        return $this->cheatType;
    }

    public function isEnabled() : bool{
        return $this->enabled;
    }

    public static function setViolations(string $name, float $amount) : void{
        ViolationHandler::setViolations($name, $amount);
    }

    public static function getCurrentViolations(string $name) : int{
        return ViolationHandler::getViolations($name);
    }

    public function getPlugin() : Mockingbird{
        return $this->plugin;
    }

    protected function getServer() : Server{
        return Server::getInstance();
    }

    protected function genericAlertData(Player $player) : array{
        return ["VL" => self::getCurrentViolations($player->getName()), "Ping" => $player->getPing()];
    }

    protected function addViolation(string $name) : void{
        if($this->getServer()->getPlayer($name)->hasPermission($this->getPlugin()->getConfig()->get("bypass_permission"))){
            return;
        }
        if($this->isLowTPS()){
            $tps = $this->getServer()->getTicksPerSecond();
            $this->getServer()->getLogger()->debug("Violation was cancelled due to low TPS ($tps)");
            return;
        }
        if($this instanceof StrictRequirements){
            if($this->getServer()->getTicksPerSecond() < StrictRequirements::MIN_TPS){
                $this->getServer()->getLogger()->debug("Strict TPS requirement was not met.");
                return;
            }
            if($this->getServer()->getPlayer($name)->getPing() > StrictRequirements::MAX_PING){
                $this->getServer()->getLogger()->debug("Strict ping requirement was not met.");
                return;
            }
        }
        $violationTime = $this->getLastViolatedTime($name);
        if($violationTime !== null){
            if($violationTime < 0.1){
                // TODO: Remove since database is no longer implemented.
                $this->punish($name, true);
                $this->getServer()->getLogger()->debug("In order to prevent the server from crashing, $name was kicked.");
                $this->previousViolationTime[$name] = microtime(true);
                return;
            } else {
                $this->previousViolationTime[$name] = microtime(true);
            }
        } else {
            $this->previousViolationTime[$name] = microtime(true);
        }
        ViolationHandler::addViolation($name, $this->getName());
    }

    protected function notifyStaff(string $name, string $cheat, array $data) : void{
        if($this->getServer()->getPlayer($name)->hasPermission($this->getPlugin()->getConfig()->get("bypass_permission"))){
            return;
        }
        if($this->isLowTPS()){
            $this->getServer()->getLogger()->debug("Alert was cancelled due to low TPS");
            return;
        }
        if(!isset($this->notifyCooldown[$name])){
            $this->notifyCooldown[$name] = microtime(true);
        } else {
            if(microtime(true) - $this->notifyCooldown[$name] >= 0.1){
                $this->notifyCooldown[$name] = microtime(true);
            } else {
                return;
            }
        }
        foreach($this->getServer()->getOnlinePlayers() as $player){
            if($player->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
                $dataReport = TextFormat::DARK_RED . "[";
                foreach($data as $dataName => $info){
                    if(array_key_last($data) !== $dataName) $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info . TextFormat::DARK_RED . " | ";
                    else $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info;
                }
                $dataReport .= TextFormat::DARK_RED . "]";
                $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . $name . TextFormat::GRAY . " has failed the check for " . TextFormat::RED . $cheat . TextFormat::RESET . " $dataReport");
            }
        }
        if(self::getCurrentViolations($name) >= $this->getPlugin()->getConfig()->get("max_violations")){
            $this->punish($name);
        }
    }

    protected function punish(string $name, bool $forced = false) : void{
        if($forced){
            $this->getPlugin()->kickPlayerTask($this->getServer()->getPlayer($name));
            return;
        }
        $punishmentType = $this->getPlugin()->getConfig()->get("punishment_type");
        switch($punishmentType){
            case "kick":
                $this->getPlugin()->kickPlayerTask($this->getServer()->getPlayer($name));
                break;
            case "ban":
                $this->getPlugin()->banPlayerTask($this->getServer()->getPlayer($name));
                break;
            case "none":
            default:
                break;
        }
    }

    protected function getLastViolatedTime(string $name) : ?float{
        return isset($this->previousViolationTime[$name]) ? microtime(true) - $this->previousViolationTime[$name] : null;
    }

    private function isLowTPS() : bool{
        return $this->getServer()->getTicksPerSecond() <= $this->getPlugin()->getConfig()->get("stop_tps");
    }

}