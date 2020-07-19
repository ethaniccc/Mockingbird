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
use ethaniccc\Mockingbird\command\ReloadModuleCommand;
use pocketmine\event\HandlerList;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Player;

class Mockingbird extends PluginBase implements Listener{

    /** @var bool */
    private $developerMode;

    /** @var array */
    private $modules = [
        "Combat" => [
            "Reach", "AutoClickerA", "AutoClickerB", "ToolboxKillaura",
            "MultiAura", "Angle"
        ],
        "Movement" => [
            "Speed", "NoSlowdown", "FastLadder", "NoWeb", "AirJump",
            "Fly", "InventoryMove", "Glide"
        ],
        "Packet" => [
            "BadPitchPacket", "AttackingWhileEating", "InvalidCreativeTransaction",
            "InvalidCraftingTransaction"
        ],
        "Other" => [
            "ChestStealer", "FastEat", "Nuker", "FastBreak", "Timer"
        ],
        "Custom" => []
    ];

    /** @var array */
    private $enabledModules = [];
    /** @var array */
    private $disabledModules = [];

    public function onEnable(){
        if(!file_exists($this->getDataFolder() . "config.yml")){
            $this->saveDefaultConfig();
        }
        $this->developerMode = is_bool($this->getConfig()->get("dev_mode")) ? $this->getConfig()->get("dev_mode") : false;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if($this->getConfig()->get("version") !== $this->getDescription()->getVersion()){
            $this->saveDefaultConfig();
        }
        @mkdir($this->getDataFolder() . "custom_modules", 0777);
        $customModules = scandir($this->getDataFolder() . "custom_modules");
        foreach($customModules as $customModule){
            $className = explode(".php", $customModule)[0];
            if($className !== "." && $className !== ".."){
                array_push($this->modules["Custom"], $className);
            }
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

    /**
     * @return string
     */
    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " : $this->getConfig()->get("prefix") . " ";
    }

    /**
     * @param Player $player
     */
    public function kickPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            $player->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were kicked from this server for unfair advantage.", false);
            Cheat::setViolations($name, 0);
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been kicked for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    /**
     * @param Player $player
     */
    public function banPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            if($this->getConfig()->get("punishment_type") === "ip-ban"){
                $this->getServer()->getIPBans()->addBan($player->getAddress(), "Unfair advantage / Hacking", null, "Mockingbird");
                foreach($this->getServer()->getOnlinePlayers() as $person){
                    if($person->getAddress() === $player->getAddress()){
                        if($person->getName() === $player->getName()){
                            $person->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were IP-banned from this server for unfair advantage.", false);
                        } else {
                            $person->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "Someone on your internet was hacking, and your IP was banned.", false);
                        }
                    }
                }
            }
            $this->getServer()->getNameBans()->addBan($name, "Unfair advantage / Hacking", null, "Mockingbird");
            Cheat::setViolations($name, 0);
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been banned for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
        }), 1);
    }

    /**
     * @return bool
     */
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
            fwrite($dataSave, "Player || CurrentVL || TotalVL || Cheats Detected\n\n");
            foreach(ViolationHandler::getSaveData() as $name => $data){
                $currentViolations = $data["CurrentVL"];
                $totalViolations = $data["TotalVL"];
                $averageTPS = $data["AverageTPS"];
                $cheats = $data["Cheats"];
                fwrite($dataSave, "$name || CurrentVL: $currentViolations || TotalVL: $totalViolations || Average TPS: $averageTPS || Cheats: " . implode(", ", $cheats) . "\n");
            }
            fclose($dataSave);
        }
    }

    /**
     * @return array
     */
    public function getEnabledModules() : array{
        return $this->enabledModules;
    }

    /**
     * @return array
     */
    public function getDisabledModules() : array{
        return $this->disabledModules;
    }

    /**
     * @param string $module
     * @return Cheat|null
     */
    public function getModuleByName(string $module) : ?Cheat{
        foreach($this->enabledModules as $mod){
            if($mod->getName() === $module){
                return $mod;
            }
        }
        foreach($this->disabledModules as $disMod){
            if($disMod->getName() === $module){
                return $disMod;
            }
        }
        return null;
    }

    /**
     * @return array
     */
    public function getAllModules() : array{
        return $this->modules;
    }

    private function loadAllModules(bool $debug = true) : void{
        $loadedModules = 0;
        foreach($this->modules as $type => $modules){
            $namespace = "ethaniccc\\Mockingbird\\cheat\\" . (strtolower($type)) . "\\";
            foreach($modules as $module){
                if($type === "Custom"){
                    require_once $this->getDataFolder() . "custom_modules/$module.php";
                }
                $class = $namespace . "$module";
                $enabled = $this->getConfig()->get("dev_mode") === true ? true : $this->getConfig()->get($module);
                if($type === "Custom"){
                    // All custom modules have to be enabled.
                    $enabled = true;
                } elseif($type === "Packet"){
                    $enabled = is_bool($this->getConfig()->get("PacketChecks")) ? $this->getConfig()->get("PacketChecks") : false;
                }
                $newModule = new $class($this, $module, $type, $enabled);
                if($newModule->isEnabled()){
                    $this->getServer()->getPluginManager()->registerEvents($newModule, $this);
                    $loadedModules++;
                    array_push($this->enabledModules, $newModule);
                } else {
                    array_push($this->disabledModules, $newModule);
                }
            }
        }
        $moduleNames = [];
        foreach($this->getEnabledModules() as $module){
            array_push($moduleNames, $module->getName());
        }
        if($debug){
            $this->getLogger()->debug(TextFormat::GREEN . "$loadedModules modules have been loaded: " . implode(", ", $moduleNames));
        } else {
            $this->getLogger()->info(TextFormat::GREEN . "$loadedModules modules have been loaded: " . implode(", ", $moduleNames));
        }
    }

    public function reloadModules() : void{
        // This is mostly just going to reload the **custom modules** only lol...
        // NOTE: This will not load the source of the custom module **sad noises**
        foreach($this->enabledModules as $module){
            HandlerList::unregisterAll($module);
        }
        unset($this->enabledModules);
        $this->enabledModules = [];
        unset($this->modules["Custom"]);
        $this->modules["Custom"] = [];
        $customModules = scandir($this->getDataFolder() . "custom_modules");
        foreach($customModules as $customModule){
            $className = explode(".php", $customModule)[0];
            if($className !== "." && $className !== ".."){
                array_push($this->modules["Custom"], $className);
            }
        }
        $this->loadAllModules(false);
    }

    private function loadAllCommands() : void{
        $commandMap = $this->getServer()->getCommandMap();
        $this->getConfig()->get("LogCommand") === true ? $commandMap->register($this->getName(), new LogCommand("logs", $this)) : $this->getLogger()->debug("Log command disabled");
        $commandMap->register($this->getName(), new ReloadModuleCommand("mbreload", $this));
    }

}