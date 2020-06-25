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

declare(strict_types=1);

namespace ethaniccc\Mockingbird;

use ethaniccc\Mockingbird\command\LogCommand;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\task\SaveDataTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Player;

class Mockingbird extends PluginBase implements Listener{

    private $developerMode = true;
    private $database;
    private $modules = [
        "Combat" => [
            "Reach", "Aimbot", "AutoClickerA"
        ],
        "Movement" => [
            "Speed"
        ],
    ];
    private $cheatsViolatedFor = [];
    private $blocked = [];

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($this->getConfig()->get("version") !== $this->getDescription()->getVersion()){
            $this->saveDefaultConfig();
        }
        if($this->getConfig()->get("keep_previous_violations") === false){
            if(file_exists($this->getDataFolder() . 'CheatData.db')) $this->getServer()->getAsyncPool()->submitTask(new SaveDataTask($this->getDataFolder(), is_bool($this->getConfig()->get("save_previous_violations")) ? $this->getConfig()->get("save_previous_violations") : false));
        }
        $this->getLogger()->debug(TextFormat::AQUA . "Mockingbird has been enabled.");
        $this->loadAllModules();
        $this->loadAllCommands();
        $this->loadDatabase();
    }

    public function loadDatabase() : void{
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) : void{
            $this->database = new \SQLite3($this->getDataFolder() . 'CheatData.db');
            $this->database->exec("CREATE TABLE IF NOT EXISTS cheatData (playerName TEXT PRIMARY KEY, violations INT);");
        }), 5);
    }

    public function getDatabase() : \SQLite3{
        return $this->database;
    }

    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " : $this->getConfig()->get("prefix") . " ";
    }

    public function addCheat(string $player, string $cheat) : void{
        if(!isset($this->cheatsViolatedFor[$player])) $this->cheatsViolatedFor[$player] = [];
        if(!in_array($cheat, $this->cheatsViolatedFor[$player])) array_push($this->cheatsViolatedFor[$player], $cheat);
    }

    public function getCheatsViolatedFor(string $name) : array{
        return !isset($this->cheatsViolatedFor[$name]) ? [] : $this->cheatsViolatedFor[$name];
    }

    public function getBlockTime() : string{
        return is_int($this->getConfig()->get("block_time")) ? "{$this->getConfig()->get("block_time")}" : "300";
    }

    public function blockPlayerTask(Player $player) : void{
        $name = $player->getName();
        if(!isset($this->blocked[$name])) $this->blocked[$name] = microtime(true);
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            if(!$player->hasPermission($this->getConfig()->get("bypass_permission"))){
                $remainingTime = round((int) $this->getBlockTime() - (microtime(true) - $this->blocked[$name]));
                $player->kick($this->getConfig()->get("block_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were blocked from this server for " . $this->getBlockTime() . " seconds due to unfair advantage.\nThere is still $remainingTime seconds remaining in the block.", false);
                Cheat::$instance->setViolations($name, 25);
            }
            $cheats = $this->getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been blocked (for {$this->getBlockTime()} seconds) for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    public function isBlocked(string $name) : bool{
        return isset($this->blocked[$name]) ? microtime(true) - $this->blocked[$name] <= (int)$this->getBlockTime() : false;
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if($this->isBlocked($name)){
            $this->blockPlayerTask($player);
        } else {
            if(!isset($this->blocked[$name])) return;
            unset($this->blocked[$name]);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))){
                    $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has just been unblocked from the server. Make sure to keep an eye out for that player.");
                }
            }
        }
    }

    private function loadAllModules() : void{
        $loadedModules = 0;
        foreach($this->modules as $type => $modules){
            $namespace = "\\ethaniccc\\Mockingbird\\cheat\\" . (strtolower($type)) . "\\";
            foreach($modules as $module){
                $class = $namespace . "$module";
                $newModule = new $class($this, $module, $type, $this->getConfig()->get($module));
                if($newModule->isEnabled()) $this->getServer()->getPluginManager()->registerEvents($newModule, $this);
                if($newModule->isEnabled()) $loadedModules++;
            }
        }
        $this->getLogger()->info(TextFormat::GREEN . "$loadedModules modules have been loaded.");
    }

    private function loadAllCommands() : void{
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register($this->getName(), new LogCommand("logs", $this));
    }

}