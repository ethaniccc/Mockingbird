<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class ToggleDebugCommand extends Command implements PluginIdentifiableCommand{

    private $plugin;

    public function __construct(Mockingbird $plugin, string $name = "mbdebug", string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->setPermission("mockingbird.debug");
        $this->setDescription("Enable debug messages in-game with the Mockingbird anti-cheat");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("You must run this command as a player");
        } else {
            if($this->testPermission($sender)){
                $user = UserManager::getInstance()->get($sender);
                $user->debug = !$user->debug;
                $user->debug ? $sender->sendMessage(Mockingbird::getInstance()->getPrefix() . TextFormat::GREEN . " Your debug messages have been enabled") : $sender->sendMessage(Mockingbird::getInstance()->getPrefix() . TextFormat::RED . " Your debug messages have been disabled");
            }
        }
    }

    public function getPlugin(): Plugin{
        return $this->plugin;
    }

}