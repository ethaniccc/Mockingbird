<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ToggleAlertsCommand extends Command implements PluginIdentifiableCommand{

    private $plugin;

    public function __construct(Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct("mbalerts", $description, $usageMessage, $aliases);
        $this->setDescription("Toggle alerts in-game with the Mockingbird anti-cheat");
        $this->setPermission("mockingbird.alerts");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("You must run this command as a player");
        } else {
            if($this->testPermission($sender)){
                if(!Mockingbird::getInstance()->getConfig()->get("alerts_enabled")){
                    $sender->sendMessage(Mockingbird::getInstance()->getPrefix() . TextFormat::RED . "Error: Alerts are disabled.");
                }
                $user = UserManager::getInstance()->get($sender);
                $user->alerts = !$user->alerts;
                $user->alerts ? $sender->sendMessage(Mockingbird::getInstance()->getPrefix() . TextFormat::GREEN . " Your alerts have been enabled") : $sender->sendMessage(Mockingbird::getInstance()->getPrefix() . TextFormat::RED . " Your alerts have been disabled");
            }
        }
    }

    public function getPlugin(): Plugin{
        return $this->plugin;
    }

}