<?php

namespace ethaniccc\Mockingbird\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class AlertsCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setPermission($this->getPlugin()->getConfig()->get("alert_permission"));
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            if($sender instanceof ConsoleCommandSender){
                $sender->sendMessage(TextFormat::RED. "This command is only intended for in-game players.");
                return;
            }
            $staff = $this->getPlugin()->getStaff($sender->getName());
            if($staff === null){
                $sender->sendMessage($this->getPlugin()->getPrefix() . "Something went wrong. Try rejoining the game.");
                return;
            }
            switch($staff->hasAlertsEnabled()){
                case true:
                    $staff->setAlertsEnabled(false);
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "Your alerts have been disabled.");
                    break;
                case false:
                    $staff->setAlertsEnabled(true);
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "Your alerts have been enabled.");
                    break;
            }
        }
    }

}