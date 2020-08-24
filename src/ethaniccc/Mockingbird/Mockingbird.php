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

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\ViolationHandler;
use ethaniccc\Mockingbird\command\AlertsCommand;
use ethaniccc\Mockingbird\command\DebugCommand;
use ethaniccc\Mockingbird\command\DisableModuleCommand;
use ethaniccc\Mockingbird\command\EnableModuleCommand;
use ethaniccc\Mockingbird\command\LogCommand;
use ethaniccc\Mockingbird\command\ReloadModuleCommand;
use ethaniccc\Mockingbird\command\ScreenshareCommand;
use ethaniccc\Mockingbird\listener\MockingbirdListener;
use ethaniccc\Mockingbird\utils\staff\Staff;
use ethaniccc\Mockingbird\utils\user\UserManager;
use pocketmine\event\HandlerList;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

class Mockingbird extends PluginBase{

    private $developerMode = false;
    private $enabledModules = [];
    private $disabledModules = [];
    private $staff = [];
    private $userManager;

    public function onEnable(){
        if(!file_exists($this->getDataFolder() . "config.yml")){
            $this->saveDefaultConfig();
        }
        $this->developerMode = is_bool($this->getConfig()->get("dev_mode")) ? $this->getConfig()->get("dev_mode") : false;
        if($this->getConfig()->get("version") !== $this->getDescription()->getVersion()){
            $this->saveDefaultConfig();
        }
        @mkdir($this->getDataFolder() . "custom_modules", 0777);
        $this->getLogger()->debug(TextFormat::AQUA . "Mockingbird has been enabled.");
        $this->loadModules();
        $this->loadModules(true);
        $this->loadAllCommands();
        $this->registerPermissions();
        if($this->isDeveloperMode()){
            $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function(int $currentTick) : void{
                $level = $this->getServer()->getLevelByName("world");
                if($level !== null){
                    $level->setTime(6000);
                }
            }), 100, 200);
        }
        $this->registerListener();
        $this->userManager = new UserManager();
    }

    public function getUserManager() : UserManager{
        return $this->userManager;
    }

    public function getPrefix() : string{
        return !is_string($this->getConfig()->get("prefix")) ? TextFormat::BOLD . TextFormat::RED . "Mockingbird> " . TextFormat::RESET : $this->getConfig()->get("prefix") . " ";
    }

    public function kickPlayerTask(Player $player) : void{
        $name = $player->getName();
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use ($player, $name) : void{
            $player->kick($this->getConfig()->get("punish_prefix") . TextFormat::RESET . "\n" . TextFormat::YELLOW . "You were kicked from this server for unfair advantage.", false);
            ViolationHandler::setViolations($name, 0);
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been kicked for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
            ViolationHandler::addTimesPunished($name);
            if($this->getConfig()->get("max_kicks") != -1 && ViolationHandler::getTimesPunished($name) >= $this->getConfig()->get("max_kicks")){
                $timesKicked = ViolationHandler::getTimesPunished($name);
                foreach($this->getServer()->getOnlinePlayers() as $staff){
                    if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been kicked $timesKicked and therefore has been banned from the server.");
                }
                $this->getServer()->getNameBans()->addBan($name, "Unfair advantage", null, "Mockingbird");
            }
        }), 1);
    }

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
            ViolationHandler::setViolations($name, 0);
            $cheats = ViolationHandler::getCheatsViolatedFor($name);
            foreach($this->getServer()->getOnlinePlayers() as $staff){
                if($staff->hasPermission($this->getConfig()->get("alert_permission"))) $staff->sendMessage($this->getPrefix() . TextFormat::RESET . TextFormat::RED . "$name has been banned for using unfair advantage on other players. They were detected for: " . implode(", ", $cheats));
            }
            ViolationHandler::addTimesPunished($name);
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

    public function getEnabledModules() : array{
        return $this->enabledModules;
    }

    public function getDisabledModules() : array{
        return $this->disabledModules;
    }

    public function getModuleByName(string $module) : ?Cheat{
        foreach($this->enabledModules as $mod){
            if(strtolower($mod->getName()) === strtolower($module)){
                return $mod;
            }
        }
        foreach($this->disabledModules as $disMod){
            if(strtolower($disMod->getName()) === strtolower($module)){
                return $disMod;
            }
        }
        return null;
    }

    public function enableModule($module) : void{
        $this->getServer()->getPluginManager()->registerEvents($module, $this);
        $module->setEnabled();
    }

    public function disableModule($module) : void{
        HandlerList::unregisterAll($module);
        $module->setEnabled(false);
    }

    public function registerStaff(string $name) : void{
        $this->staff[$name] = new Staff($name);
    }

    public function getStaff(string $name) : ?Staff{
        return isset($this->staff[$name]) ? $this->staff[$name] : null;
    }

    public function loadModule(Cheat $module) : void{
        $this->getServer()->getPluginManager()->registerEvents($module, $this);
    }

    private function loadModules(bool $custom = false, bool $debug = true) : void{
        $loadedModules = 0;
        if(!$custom){
            foreach(scandir($this->getFile() . '/src/ethaniccc/Mockingbird/cheat') as $type){
                if(is_dir($this->getFile() . "/src/ethaniccc/Mockingbird/cheat/$type") && !in_array($type, ['.', '..'])){
                    foreach(scandir($this->getFile() . "/src/ethaniccc/Mockingbird/cheat/$type") as $module){
                        $currentPath = "ethaniccc\\Mockingbird\\cheat\\$type\\";
                        if(is_dir($this->getFile() . "/src/ethaniccc/Mockingbird/cheat/$type/$module") && !in_array($module, ['.', '..'])){
                            foreach(scandir($this->getFile() . "/src/ethaniccc/Mockingbird/cheat/$type/$module") as $subModule){
                                if(!is_dir($this->getFile() . "/src/ethaniccc/Mockingbird/cheat/$type/$module/$subModule")){
                                    $className = explode(".php", $subModule)[0];
                                    $class = $currentPath . "$module\\$className";
                                    $enabled = (bool) $this->getConfig()->get($className);
                                    $enabled = $this->isDeveloperMode() ? true : $enabled;
                                    $newDetection = new $class($this, $className, $type, $enabled);
                                    $this->loadModule($newDetection);
                                    $newDetection->isEnabled() ? $this->enabledModules[] = $newDetection : $this->disabledModules[] = $newDetection;
                                    if($newDetection->isEnabled()){
                                        $loadedModules++;
                                    }
                                }
                            }
                        } elseif(!is_dir($module)){
                            $className = explode(".php", $module)[0];
                            $class = $currentPath . $className;
                            $enabled = (bool) $this->getConfig()->get($className);
                            if($type === "packet"){
                                $enabled = (bool) $this->getConfig()->get("PacketChecks");
                            }
                            $enabled = $this->isDeveloperMode() ? true : $enabled;
                            $newDetection = new $class($this, $className, $type, $enabled);
                            $this->loadModule($newDetection);
                            $newDetection->isEnabled() ? $this->enabledModules[] = $newDetection : $this->disabledModules[] = $newDetection;
                            if($newDetection->isEnabled()){
                                $loadedModules++;
                            }
                        }
                    }
                }
            }
            $debug ? $this->getLogger()->debug("$loadedModules general modules have been loaded.") : $this->getLogger()->info(TextFormat::GREEN . "$loadedModules general modules have been loaded.");
        } else {
            foreach(scandir($this->getDataFolder() . "custom_modules") as $customModule){
                if(is_dir($customModule)){
                    break;
                }
                $path = $this->getDataFolder() . "custom_modules/$customModule";
                require_once $path;
                $class = explode(".php", $customModule)[0];
                // hardcoded type and enabled parameters
                $this->loadModule(new $class($this, $class, "Custom", true));
                $loadedModules++;
            }
            $debug ? $this->getLogger()->debug("$loadedModules custom modules have been loaded.") : $this->getLogger()->info(TextFormat::GREEN . "$loadedModules custom modules have been loaded.");
        }
    }

    public function reloadModules() : void{
        // This is mostly just going to reload the **custom modules** only lol...
        // NOTE: This will not load the source of the custom module **sad noises**
        foreach($this->enabledModules as $module){
            HandlerList::unregisterAll($module);
            // destruct the object as it is not going to be used anymore when we reload modules.
            $module = null;
        }
        unset($this->enabledModules);
        $this->enabledModules = [];
        unset($this->disabledModules);
        $this->enabledModules = [];
        $this->loadModules(false, false);
        $this->loadModules(true, false);
    }

    private function loadAllCommands() : void{
        $commandMap = $this->getServer()->getCommandMap();
        $this->getConfig()->get("LogCommand") ? $commandMap->register($this->getName(), new LogCommand("mblogs", $this)) : $this->getLogger()->debug("Log command disabled");
        $this->getConfig()->get("ScreenshareCommand") ? $commandMap->register($this->getName(), new ScreenshareCommand("mbscreenshare", $this)) :  $this->getLogger()->debug("Screenshare command disabled");
        $commandMap->register($this->getName(), new ReloadModuleCommand("mbreload", $this));
        $commandMap->register($this->getName(), new EnableModuleCommand("mbenable", $this));
        $commandMap->register($this->getName(), new DisableModuleCommand("mbdisable", $this));
        $commandMap->register($this->getName(), new AlertsCommand("mbalerts", $this));
        $commandMap->register($this->getName(), new DebugCommand("mbdebug", $this));
    }

    private function registerPermissions() : void{
        $permissions = [
            new Permission($this->getConfig()->get("alert_permission"), "Get alerts from the Mockingbird Anti-Cheat."),
            new Permission($this->getConfig()->get("log_permission"), "Check logs of players from the Mockingbird Anti-Cheat."),
            new Permission($this->getConfig()->get("module_permission"), "Manage Mockingbird modules."),
            new Permission($this->getConfig()->get("bypass_permission"), "Exempt yourself from getting flagged by the Mockingbird AntiCheat."),
            new Permission($this->getConfig()->get("screenshare_permission"), "'Screenshare' a selected player.")
        ];
        foreach($permissions as $permission){
            PermissionManager::getInstance()->addPermission($permission);
        }
    }

    private function registerListener() : void{
        $this->getServer()->getPluginManager()->registerEvents(new MockingbirdListener($this), $this);
    }

}