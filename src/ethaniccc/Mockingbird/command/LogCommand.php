<?php

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LogCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setDescription("Get the cheat logs of a player.");
        $this->setPermission($this->getPlugin()->getConfig()->get("log_permission"));
        $this->setUsage(TextFormat::RED . "Usage: /logs <player>");
    }

    public function getPlugin() : Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
       if($this->testPermission($sender)){
           if(!isset($args[0])){
               $sender->sendMessage($this->getUsage());
           } else {
               $player = Server::getInstance()->getPlayer($args[0]);
               if($player !== null){
                   $name = $player->getName();
                   $violations = Cheat::getCurrentViolations($name);
                   $cheats = $this->getPlugin()->getCheatsViolatedFor($name);
                   if($violations <= 0){
                       $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . "The specified player has no logs.");
                   } else {
                       $sender->sendMessage(TextFormat::RESET . TextFormat::RED . "====----====\nPlayer: " . TextFormat::GRAY . $args[0] . "\n" . TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats) . "\n" . TextFormat::RED . "Total Violations: " . TextFormat::GRAY . $violations . "\n" . TextFormat::RED . "====----====");
                   }
               } else {
                   $violations = Cheat::getCurrentViolations($args[0]);
                   $cheats = $this->getPlugin()->getCheatsViolatedFor($args[0]);
                   if($violations <= 0){
                       $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . "The specified player has no logs.");
                   } else {
                       $sender->sendMessage(TextFormat::RESET . TextFormat::RED . "====----====\nPlayer: " . TextFormat::GRAY . $args[0] . "\n" . TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats) . "\n" . TextFormat::RED . "Total Violations: " . TextFormat::GRAY . $violations . "\n" . TextFormat::RED . "====----====");
                   }
               }
           }
       }
    }

}