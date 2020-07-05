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
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Player;

class Mockingbird extends PluginBase implements Listener{

    private $developerMode;
    private $database;
    private $modules = [
        "Combat" => [
            "Reach", "AutoClickerA", "AutoClickerB", "AutoClickerC",
            "AutoClickerD", "ToolboxKillaura", "NoKnockback"
        ],
        "Movement" => [
            "Speed", "NoSlowdown", "FastLadder", "NoWeb", "AirJump",
            "Fly"
        ],
        "Other" => [
            "ChestStealer", "FastEat", "Nuker"
        ]
    ];
    private $cheatsViolatedFor = [];
    private $blocked = [];

    public function onEnable(){
        $this->developerMode = is_bool($this->getConfig()->get("dev_mode")) ? $this->getConfig()->get("dev_mode") : false;
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
        if($this->isDeveloperMode()){
            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void{
                $level = $this->getServer()->getLevelByName("world");
                if($level !== null){
                    $level->setTime(6000);
                }
            }), 100, 200);
        }
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

    public function kickPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            $player->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were kicked from this server for unfair advantage.", false);
            Cheat::setViolations($name, 20);
            $cheats = $this->getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been kicked for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    public function banPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            $player->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were banned from this server for unfair advantage.", false);
            $this->getServer()->getNameBans()->addBan($name, "Unfair advantage / Hacking", null, "Mockingbird");
            Cheat::setViolations($name, 20);
            $cheats = $this->getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been banned for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    public function isDeveloperMode() : bool{
        return $this->developerMode;
    }

    private function loadAllModules() : void{
        $loadedModules = 0;
        foreach($this->modules as $type => $modules){
            $namespace = "\\ethaniccc\\Mockingbird\\cheat\\" . (strtolower($type)) . "\\";
            foreach($modules as $module){
                $class = $namespace . "$module";
                $newModule = new $class($this, $module, $type, $this->getConfig()->get("dev_mode") === true ? true : $this->getConfig()->get($module));
                if($newModule->isEnabled()) $this->getServer()->getPluginManager()->registerEvents($newModule, $this);
                if($newModule->isEnabled()) $loadedModules++;
            }
        }
        $this->getLogger()->debug(TextFormat::GREEN . "$loadedModules modules have been loaded.");
    }

    private function loadAllCommands() : void{
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register($this->getName(), new LogCommand("logs", $this));
    }

}