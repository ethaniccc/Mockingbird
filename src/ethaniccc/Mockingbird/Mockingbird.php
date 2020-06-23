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
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class Mockingbird extends PluginBase{

    private $developerMode = true;
    private $database;
    private $modules = [
        "Combat" => [
            "Reach", "Aimbot", "AutoClickerA"
        ],
        "Movement" => [
            "Speed"
        ],
        "Packet" => [
            "BadPitch"
        ]
    ];
    private $cheatsViolatedFor = [];

    public function onEnable(){
        if($this->getConfig()->get("version") !== $this->getDescription()->getVersion()){
            $this->saveDefaultConfig();
        }
        if($this->getConfig()->get("keep_previous_violations") === false){
            if(file_exists($this->getDataFolder() . 'CheatData.db')) unlink($this->getDataFolder() . 'CheatData.db');
        }
        $this->getLogger()->debug(TextFormat::AQUA . "Mockingbird has been enabled.");
        $this->database = new \SQLite3($this->getDataFolder() . 'CheatData.db');
        $this->database->exec("CREATE TABLE IF NOT EXISTS cheatData (playerName TEXT PRIMARY KEY, violations INT);");
        $this->loadAllModules();
        $this->loadAllCommands();
    }

    public function getDatabase() : \SQLite3{
        return $this->database;
    }

    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " : $this->getConfig()->get("prefix") . " ";
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

    public function addCheat(string $player, string $cheat) : void{
        if(!isset($this->cheatsViolatedFor[$player])) $this->cheatsViolatedFor[$player] = [];
        if(!in_array($cheat, $this->cheatsViolatedFor[$player])) array_push($this->cheatsViolatedFor[$player], $cheat);
    }

    public function getCheatsViolatedFor(string $name) : array{
        return !isset($this->cheatsViolatedFor[$name]) ? [] : $this->cheatsViolatedFor[$name];
    }

}