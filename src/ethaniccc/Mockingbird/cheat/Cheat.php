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

use ethaniccc\Mockingbird\cheat\Blatant;
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

    private $requiredTPS;
    private $requiredPing;

    private $blatantViolations = [];
    private $maxBlatantViolations;

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
        return ViolationHandler::getCurrentViolations($name);
    }

    public function getPlugin() : Mockingbird{
        return $this->plugin;
    }

    public function setRequiredTPS(float $tps){
        if(!$this instanceof StrictRequirements){
            throw new \Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        } else {
            $this->requiredTPS = $tps;
        }
    }

    public function getRequiredTPS(){
        if(!$this instanceof StrictRequirements){
            throw new \Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        }
        return $this->requiredTPS === null ? 19 : $this->requiredTPS;
    }

    public function setRequiredPing(int $ping){
        if(!$this instanceof StrictRequirements){
            throw new \Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        } else {
            $this->requiredPing = $ping;
        }
    }

    public function getRequiredPing(){
        if(!$this instanceof StrictRequirements){
            throw new \Exception("Module {$this->getName()} is not an instance of StrictRequirements.");
        }
        return $this->requiredPing === null ? 200 : $this->requiredPing;
    }

    public function getMaxViolations(){
        if(!$this instanceof Blatant){
            throw new \Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        return $this->maxBlatantViolations;
    }

    public function setMaxViolations(int $violations){
        if(!$this instanceof Blatant){
            throw new \Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        $this->maxBlatantViolations = $violations;
    }

    public function resetBlatantViolations(string $name){
        if(!$this instanceof Blatant){
            throw new \Exception("Module {$this->getName()} is not an instance of Blatant");
        }
        $this->blatantViolations[$name] = 0;
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
            if($this->getServer()->getPlayer($name)->getPing() > $this->getRequiredPing()){
                $this->getServer()->getLogger()->debug("Ping requirements were not met for {$this->getName()} (Ping: {$this->getServer()->getPlayer($name)->getPing()})");
                return;
            }
            if($this->getServer()->getTicksPerSecond() < $this->getRequiredTPS()){
                $this->getServer()->getLogger()->debug("TPS requirements were not met for {$this->getName()} (TPS: {$this->getServer()->getTicksPerSecond()})");
                return;
            }
        }
        ViolationHandler::addViolation($name, $this->getName());
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

    protected function notifyStaff(string $name, string $cheat, array $data) : void{
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
        if($this->getPlugin()->getConfig()->get("alerts") === true){
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
        }
    }

    protected function punish(string $name) : void{
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

    private function isLowTPS() : bool{
        return $this->getServer()->getTicksPerSecond() <= $this->getPlugin()->getConfig()->get("stop_tps");
    }

}