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

use ethaniccc\Mockingbird\event\MockingbirdCheatEvent;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\AsyncClosureTask;
use pocketmine\event\Cancellable;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class Cheat implements Listener{

    private $cheatName;
    private $cheatType;
    private $settings;
    private $enabled;

    private $lastViolationTime = [];

    private $requiredTPS = 20;
    private $requiredPing = 10000;

    private $preVL = [];

    private $plugin;

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        $this->cheatName = $cheatName;
        $this->cheatType = $cheatType;
        if($settings === null){
            throw new \Exception("$cheatName received invalid settings");
        }
        $this->settings = $settings;
        $this->enabled = $this->getSetting("enabled");
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

    public function setEnabled(bool $enabled = true) : void{
        $this->enabled = $enabled;
    }

    public function getSettings() : array{
        return $this->settings;
    }

    public function getSetting(string $key){
        return $this->getSettings()[$key] ?? null;
    }

    public function basicFailData(Player $player) : array{
        return ["{player}" => $player->getName()];
    }

    public function formatFailMessage(array $data = []) : string{
        $failMessage = $this->getSetting("message");
        foreach($data as $key => $info){
            $failMessage = str_replace($key, $info, $failMessage);
        }
        return $failMessage;
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

    public function setRequiredTPS(float $tps) : void{
        $this->requiredTPS = $tps;
    }

    public function getRequiredTPS() : float{
        return $this->requiredTPS;
    }

    public function setRequiredPing(int $ping) : void{
        $this->requiredPing = $ping;
    }

    public function getRequiredPing() : int{
        return $this->requiredPing;
    }

    /**
     * @return Server
     */
    protected function getServer() : Server{
        return Server::getInstance();
    }

    /**
     * @param Event $event
     */
    protected function suppress(Event $event) : void{
        if($this->getPlugin()->getConfig()->get("supression")){
            if($event instanceof MoveEvent){
                $event->getPlayer()->teleport(new Vector3($event->getPlayer()->lastX, $event->getPlayer()->lastY, $event->getPlayer()->lastZ));
            } elseif($event instanceof Cancellable){
                $event->setCancelled();
            }
        }
    }

    /**
     * @param Player $player
     * @param string $message
     * @param array $extraData
     * @param string $debugMessage
     */
    protected function fail(Player $player, string $message, array $extraData = [], string $debugMessage = null){
        $name = $player->getName();
        $isExempt = (!$this->isEnabled()
            || $player->hasPermission($this->getPlugin()->getConfig()->get("bypass_permission"))
            || $this->isLowTPS()
            || $this instanceof StrictRequirements ? ($player->getPing() > $this->getRequiredPing() || $this->getServer()->getTicksPerSecond() < $this->getRequiredTPS()) : false
        );
        if(!$isExempt){
            $addedViolations = 1;
            if($this->getPlugin()->getConfig()->get("dynamic_violations")){
                if(isset($this->lastViolationTime[$name])){
                    $timeDiff = $this->getServer()->getTick() - $this->lastViolationTime[$name];
                    if($timeDiff < 20 && $timeDiff !== 0){
                        $addedViolations = round(10 / $timeDiff, 0);
                    } elseif($timeDiff === 0){
                        $addedViolations = 20;
                    } else {
                        $addedViolations = 1;
                    }
                } else {
                    $addedViolations = 1;
                }
                $this->lastViolationTime[$name] = $this->getServer()->getTick();
            }
            $cheatEvent = new MockingbirdCheatEvent($player, $this, $message, $addedViolations, $extraData, $debugMessage);
            $cheatEvent->call();
            if(!$cheatEvent->isCancelled()){
                if($debugMessage !== null){
                    $this->debugNotify($debugMessage);
                }
                $addedViolations = $cheatEvent->getAddedViolations();
                ViolationHandler::addViolation($name, $this->getName(), $addedViolations);
                foreach(Server::getInstance()->getOnlinePlayers() as $staff){
                    if($staff->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
                        $registeredStaff = $this->getPlugin()->getStaff($staff->getName());
                        if($registeredStaff !== null){
                            if($registeredStaff->hasAlertsEnabled()){
                                $staff->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::GRAY . "(" . TextFormat::AQUA . $this->getName() . TextFormat::GRAY . ") " . TextFormat::RESET . TextFormat::RED . $message . TextFormat::DARK_RED . " [" . TextFormat::WHITE . "VL: " . TextFormat::RED . ViolationHandler::getCurrentViolations($name) . TextFormat::DARK_RED . "]");
                            }
                        }
                    }
                }
                if(ViolationHandler::getCurrentViolations($name) >= $this->getPlugin()->getConfig()->get("max_violations")){
                    $this->punish($name);
                }
            }
        }
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
        $this->getServer()->getAsyncPool()->submitTask(new AsyncClosureTask(function() use($message){
            $log = @fopen("plugin_data/Mockingbird/debug_log.txt", "a");
            fwrite($log, "$message\n");
            fclose($log);
        }));
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