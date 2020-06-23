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

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class Mockingbird extends PluginBase{

    private $developerMode = true;
    private $database;
    private $modules = [
        "Combat" => [
            "Reach", "Aimbot"
        ],
        "Movement" => [
            "Speed"
        ],
        "Packet" => [
            "BadPitch"
        ]
    ];

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
    }

    public function getDatabase() : \SQLite3{
        return $this->database;
    }

    public function getAlertPermission() : string{
        return !is_string($this->getConfig()->get("alert_permission")) ? "mockingbird.alerts" : $this->getConfig()->get("alert_permission");
    }

    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " : $this->getConfig()->get("prefix") . " ";
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

    public function isDeveloperMode() : bool{
        return $this->developerMode;
    }

}