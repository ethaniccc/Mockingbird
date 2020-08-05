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

use ethaniccc\Mockingbird\Mockingbird;
use Exception;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Cheat implements Listener{

    /** @var string */
    private $cheatName;

    /** @var string */
    private $cheatType;

    /** @var bool */
    private $enabled;

    /** @var array */
    private $notifyCooldown = [];

    /** @var float */
    private $requiredTPS;
    /** @var int */
    private $requiredPing;

    /** @var array */
    private $blatantViolations = [];
    /** @var int */
    private $maxBlatantViolations;

    /** @var array */
    private $lastViolationTime = [];

    /** @var array */
    private $preVL = [];

    /** @var Mockingbird */
    private $plugin;

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        $this->cheatName = $cheatName;
        $this->cheatType = $cheatType;
        $this->enabled = $enabled;
        $this->plugin = $plugin;
    }

    /**
     * @return string
     */
    public function getName() : string{
        return $this->cheatName;
    }

    /**
     * @return string
     */
    public function getType() : string{
        return $this->cheatType;
    }

    /**
     * @return bool
     */
    public function isEnabled() : bool{
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled = true) : void{
        $this->enabled = $enabled;
    }

    /**
     * @param string $name
     * @param float $amount
     */
    public static function setViolations(string $name, float $amount) : void{
        ViolationHandler::setViolations($name, $amount);
    }

    /**
     * @param string $name
     * @return int
     */
    public static function getCurrentViolations(string $name) : int{
        return ViolationHandler::getCurrentViolations($name);
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin() : Mockingbird{
        return $this->plugin;
    }

    /**
     * @param string $name
     */
    public function addPreVL(string $name) : void{
        if(!isset($this->preVL[$name])){
            $this->preVL[$name] = 0.0;
        }
        $this->preVL[$name] += 1;
    }

    /**
     * @param string $name
     * @return float
     */
    public function getPreVL(string $name) : float{
        return isset($this->preVL[$name]) ? $this->preVL[$name] : 0;
    }

    /**
     * @param string $name
     * @param float $multiplier
     */
    public function lowerPreVL(string $name, float $multiplier = 0.75) : void{
        if(isset($this->preVL[$name])){
            $this->preVL[$name] *= $multiplier;
        }
    }

    /**
     * @param float $tps
     * @throws Exception
     */
    public function setRequiredTPS(float $tps){
        if(!$this instanceof StrictRequirements){
            throw new Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        } else {
            $this->requiredTPS = $tps;
        }
    }

    /**
     * @return float|int
     * @throws Exception
     */
    public function getRequiredTPS(){
        if(!$this instanceof StrictRequirements){
            throw new Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        }
        return $this->requiredTPS === null ? 19 : $this->requiredTPS;
    }

    /**
     * @param int $ping
     * @throws Exception
     */
    public function setRequiredPing(int $ping){
        if(!$this instanceof StrictRequirements){
            throw new Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        } else {
            $this->requiredPing = $ping;
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getRequiredPing(){
        if(!$this instanceof StrictRequirements){
            throw new Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        }
        return $this->requiredPing === null ? 200 : $this->requiredPing;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getMaxViolations(){
        if(!$this instanceof Blatant){
            throw new Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        return $this->maxBlatantViolations;
    }

    /**
     * @param int $violations
     * @throws Exception
     */
    public function setMaxViolations(int $violations){
        if(!$this instanceof Blatant){
            throw new Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        $this->maxBlatantViolations = $violations;
    }

    /**
     * @param string $name
     * @throws Exception
     */
    public function resetBlatantViolations(string $name){
        if(!$this instanceof Blatant){
            throw new Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        $this->blatantViolations[$name] = 0;
    }

    /**
     * @return Server
     */
    protected function getServer() : Server{
        return Server::getInstance();
    }

    /**
     * @param Player $player
     * @return array
     */
    protected function genericAlertData(Player $player) : array{
        return ["VL" => self::getCurrentViolations($player->getName()), "Ping" => $player->getPing()];
    }

    /**
     * @param string $name
     */
    protected function addViolation(string $name) : void{
        if(!$this->isEnabled()){
            return;
        }
        if($this->getServer()->getPlayer($name)->hasPermission($this->getPlugin()->getConfig()->get("bypass_permission"))){
            return;
        }
        if($this->isLowTPS()){
            $tps = $this->getServer()->getTicksPerSecond();
            $this->getServer()->getLogger()->debug("Violation was cancelled due to low TPS ($tps)");
            return;
        }
        if($this instanceof StrictRequirements){
            if($this->getServer()->getPlayer($name)->getPing() > $this->getRequiredPing()){
                $this->getServer()->getLogger()->debug("Ping requirements were not met for {$this->getName()} (Ping: {$this->getServer()->getPlayer($name)->getPing()})");
                return;
            }
            if($this->getServer()->getTicksPerSecond() < $this->getRequiredTPS()){
                $this->getServer()->getLogger()->debug("TPS requirements were not met for {$this->getName()} (TPS: {$this->getServer()->getTicksPerSecond()})");
                return;
            }
        }
        $counter = 1;
        if($this->getPlugin()->getConfig()->get("dynamic_violations")){
            if(isset($this->lastViolationTime[$name])){
                $timeDiff = $this->getServer()->getTick() - $this->lastViolationTime[$name];
                if($timeDiff < 20 && $timeDiff !== 0){
                    $counter = round(10 / $timeDiff, 0);
                } elseif($timeDiff === 0){
                    $counter = 20;
                } else {
                    $counter = 1;
                }
            } else {
                $counter = 1;
            }
            $this->lastViolationTime[$name] = $this->getServer()->getTick();
        }
        ViolationHandler::addViolation($name, $this->getName(), $counter);
        if($this instanceof Blatant){
            if(!isset($this->blatantViolations[$name])){
                $this->blatantViolations[$name] = 0;
            }
            $this->blatantViolations[$name] += 1;
            if($this->blatantViolations[$name] >= $this->getMaxViolations()){
                $this->punish($name);
            }
        }
        if(self::getCurrentViolations($name) >= $this->getPlugin()->getConfig()->get("max_violations")){
            $this->punish($name);
        }
    }

    /**
     * @param string $name
     * @param string $cheat
     * @param array $data
     */
    protected function notifyStaff(string $name, string $cheat, array $data) : void{
        if(!$this->isEnabled()){
            return;
        }
        if($this->getServer()->getPlayer($name)->hasPermission($this->getPlugin()->getConfig()->get("bypass_permission"))){
            return;
        }
        if($this->isLowTPS()){
            $this->getServer()->getLogger()->debug("Alert was cancelled due to low TPS ({$this->getServer()->getTicksPerSecond()})");
            return;
        }
        if(!isset($this->notifyCooldown[$name])){
            $this->notifyCooldown[$name] = microtime(true);
        } else {
            if(microtime(true) - $this->notifyCooldown[$name] >= 1){
                $this->notifyCooldown[$name] = microtime(true);
            } else {
                return;
            }
        }
        $dataReport = TextFormat::DARK_RED . "[";
        foreach($data as $dataName => $info){
            if(array_key_last($data) !== $dataName) $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info . TextFormat::DARK_RED . " | ";
            else $dataReport .= TextFormat::GRAY . $dataName . ": " . TextFormat::RED . $info;
        }
        $dataReport .= TextFormat::DARK_RED . "]";
        foreach($this->getServer()->getOnlinePlayers() as $player){
            if($player->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
                $staff = $this->getPlugin()->getStaff($player->getName());
                if($staff === null){
                    break;
                }
                if($staff->hasAlertsEnabled()){
                    $player->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . $name . TextFormat::GRAY . " has failed the check for " . TextFormat::RED . $cheat . TextFormat::RESET . " $dataReport");
                }
            }
        }
        $this->getServer()->getLogger()->info($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . $name . TextFormat::GRAY . " has failed the check for " . TextFormat::RED . $cheat . TextFormat::RESET . " $dataReport");
    }

    /**
     * @param string $name
     */
    protected function punish(string $name) : void{
        if(!$this->isEnabled()){
            return;
        }
        $punishmentType = $this->getPlugin()->getConfig()->get("punishment_type");
        switch($punishmentType){
            case "kick":
                $this->getPlugin()->kickPlayerTask($this->getServer()->getPlayer($name));
                break;
            case "ban":
            case "ip-ban":
                $this->getPlugin()->banPlayerTask($this->getServer()->getPlayer($name));
                break;
            case "none":
            default:
                break;
        }
    }

    /**
     * @param string $message
     */
    protected function debug(string $message) : void{
        if(!$this->isEnabled()){
            return;
        }
        $this->getServer()->getLogger()->debug("[Mockingbird || {$this->getName()}]: $message");
    }

    /**
     * @param string $message
     */
    protected function debugNotify(string $message) : void{
        if(!$this->isEnabled()){
            return;
        }
        foreach($this->getServer()->getOnlinePlayers() as $player){
            $staff = $this->getPlugin()->getStaff($player->getName());
            if($staff === null){
                break;
            }
            if($staff->hasDebugMessagesEnabled()){
                $player->sendMessage(TextFormat::DARK_RED . TextFormat::BOLD . "[MB || {$this->getName()}] " . TextFormat::RESET . TextFormat::DARK_GRAY . $message);
            }
        }
        $this->debug($message);
    }

    /**
     * @return bool
     */
    private function isLowTPS() : bool{
        return $this->getServer()->getTicksPerSecond() <= $this->getPlugin()->getConfig()->get("stop_tps");
    }

}