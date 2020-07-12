<?php

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ReloadModuleCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setPermission($this->getPlugin()->getConfig()->get("reload_permission"));
        $this->setDescription("Reload Mockingbird modules");
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin() : Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            $this->getPlugin()->reloadModules();
            $sender->sendMessage(TextFormat::GREEN . "Mockingbird modules are being re-loaded, check console.");
        }
    }

}