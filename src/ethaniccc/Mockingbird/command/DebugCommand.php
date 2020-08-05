<?php

namespace ethaniccc\Mockingbird\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class DebugCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    /**
     * DebugCommand constructor.
     * @param string $name
     * @param Mockingbird $plugin
     * @param string $description
     * @param string|null $usageMessage
     * @param array $aliases
     */
    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setPermission($this->getPlugin()->getConfig()->get("alert_permission"));
        $this->setDescription("Toggle debug messages for the Mockingbird Anti-Cheat.");
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin() : Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            if($sender instanceof ConsoleCommandSender){
                $sender->sendMessage("This command is intended for in-game staff only.");
                return;
            }

            $staff = $this->getPlugin()->getStaff($sender->getName());
            if($staff === null){
                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "Something went wrong, try re-joining the game.");
                return;
            }

            switch($staff->hasDebugMessagesEnabled()){
                case true:
                    $staff->setDebugMessagesEnabled(false);
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "Debug messages have been disabled.");
                    break;
                case false:
                    $staff->setDebugMessagesEnabled();
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "Debug messages have been enabled.");
                    break;
            }
        }
    }

}