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

use ethaniccc\Mockingbird\cheat\ViolationHandler;
use ethaniccc\Mockingbird\command\LogCommand;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Player;

class Mockingbird extends PluginBase implements Listener{

    private $developerMode;
    private $modules = [
        "Combat" => [
            "Reach", "AutoClickerA", "AutoClickerB", "ToolboxKillaura",
            "MultiAura"
        ],
        "Movement" => [
            "Speed", "NoSlowdown", "FastLadder", "NoWeb", "AirJump",
            "Fly", "InventoryMove",
        ],
        "Packet" => [
            "BadPitchPacket", "AttackingWhileEating", "InvalidCreativeTransaction"
        ],
        "Other" => [
            "ChestStealer", "FastEat", "Nuker", "FastBreak"
        ]
    ];
    private $enabledModules = [];

    public function onEnable(){
        $this->developerMode = is_bool($this->getConfig()->get("dev_mode")) ? $this->getConfig()->get("dev_mode") : false;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($this->getConfig()->get("version") !== $this->getDescription()->getVersion()){
            $this->saveDefaultConfig();
        }
        $this->getLogger()->debug(TextFormat::AQUA . "Mockingbird has been enabled.");
        $this->loadAllModules();
        $this->loadAllCommands();
        if($this->isDeveloperMode()){
            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void{
                $level = $this->getServer()->getLevelByName("world");
                if($level !== null){
                    $level->setTime(6000);
                }
            }), 100, 200);
        }
    }

    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " : $this->getConfig()->get("prefix") . " ";
    }

    public function kickPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            $player->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were kicked from this server for unfair advantage.", false);
            Cheat::setViolations($name, 20);
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
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
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been banned for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    public function isDeveloperMode() : bool{
        return $this->developerMode;
    }

    public function onDisable(){
        if($this->getConfig()->get("save_previous_violations")){
            $this->getLogger()->debug("Saving log information...");
            if(empty(ViolationHandler::getSaveData())){
                $this->getLogger()->debug("No information to save.");
                return;
            }
            @mkdir($this->getDataFolder() . 'previous_data');
            $count = count(scandir($this->getDataFolder() . "previous_data")) - 2 + 1;
            $dataSave = fopen($this->getDataFolder() . "previous_data/SaveData{$count}.txt", "a");
            foreach(ViolationHandler::getSaveData() as $name => $data){
                $violations = $data["Violations"];
                $cheats = $data["Cheats"];
                fwrite($dataSave, "Player: $name || Violations: $violations || Cheats: " . implode(", ", $cheats) . "\n");
            }
            fclose($dataSave);
        }
    }

    public function getEnabledModules() : array{
        return $this->enabledModules;
    }

    private function loadAllModules() : void{
        $loadedModules = 0;
        foreach($this->modules as $type => $modules){
            $namespace = "\\ethaniccc\\Mockingbird\\cheat\\" . (strtolower($type)) . "\\";
            foreach($modules as $module){
                $class = $namespace . "$module";
                $newModule = new $class($this, $module, $type, $this->getConfig()->get("dev_mode") === true ? true : $this->getConfig()->get($module));
                if($newModule->isEnabled()){
                    $this->getServer()->getPluginManager()->registerEvents($newModule, $this);
                    $loadedModules++;
                    array_push($this->enabledModules, $newModule);
                }
            }
        }
        $this->getLogger()->debug(TextFormat::GREEN . "$loadedModules modules have been loaded.");
    }

    private function loadAllCommands() : void{
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register($this->getName(), new LogCommand("logs", $this));
    }

}