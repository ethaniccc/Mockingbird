<?php

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\cheat\ViolationHandler;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LogCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    /** @var array */
    private $ids = [];

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setDescription("Get the cheat logs of a player.");
        $this->setPermission($this->getPlugin()->getConfig()->get("log_permission"));
        switch($this->getPlugin()->getConfig()->get("log_command_type")){
            case "normal":
                $this->setUsage(TextFormat::RED . "/logs <player>");
                break;
            case "UI":
                $this->setUsage(TextFormat::RED . "/logs");
                break;
        }
    }

    public function getPlugin() : Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
       if($this->testPermission($sender)){
           switch($this->getPlugin()->getConfig()->get("log_command_type")){
               case "normal":
                   if(!isset($args[0])){
                       $sender->sendMessage($this->getUsage());
                   }
                   $player = Server::getInstance()->getPlayer($args[0]);
                   if($player !== null){
                       $name = $player->getName();
                       $currentViolations = Cheat::getCurrentViolations($name);
                       $currentViolations = ViolationHandler::getAllViolations($name);
                       $cheats = ViolationHandler::getCheatsViolatedFor($name);
                       if($currentViolations <= 0){
                           $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . "The specified player has no logs.");
                       } else {
                           $sender->sendMessage(TextFormat::RESET . TextFormat::RED . "====----====\nPlayer: " . TextFormat::GRAY . $args[0] . "\n" . TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats) . "\n" . TextFormat::RED . "Current Violations: " . TextFormat::GRAY . $currentViolations . "\n" . TextFormat::RED . "Total Violations: " . TextFormat::GRAY . "$currentViolations\n" . TextFormat::RED . "====----====");
                       }
                   } else {
                       $currentViolations = Cheat::getCurrentViolations($args[0]);
                       $currentViolations = ViolationHandler::getAllViolations($args[0]);
                       $cheats = ViolationHandler::getCheatsViolatedFor($args[0]);
                       if($currentViolations <= 0){
                           $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RESET . TextFormat::RED . "The specified player has no logs.");
                       } else {
                           $sender->sendMessage(TextFormat::RESET . TextFormat::RED . "====----====\nPlayer: " . TextFormat::GRAY . $args[0] . "\n" . TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats) . "\n" . TextFormat::RED . "Current Violations: " . TextFormat::GRAY . $currentViolations . "\n" . TextFormat::RED . "Total Violations: " . TextFormat::GRAY . "$currentViolations\n" . TextFormat::RED . "====----====");
                       }
                   }
                   break;
               case "UI":
                   foreach($this->ids as $id => $value){
                       unset($this->ids[$id]);
                   }
                   if(!$sender instanceof Player){
                       $sender->sendMessage(TextFormat::RED . "You must run this command as a player!");
                       return;
                   }
                   $form = new SimpleForm(function(Player $player, $data = null){
                       if($data !== null){
                           if(!isset($this->ids[$data])){
                               $player->sendMessage("Something went wrong!");
                               return;
                           }
                           $playerInfoForm = new SimpleForm(function(Player $player, $data = null){
                           });
                           $playerInfoForm->setTitle(TextFormat::BOLD . "{$this->ids[$data]}'s Logs");
                           $info = ViolationHandler::getPlayerData($this->ids[$data]);
                           if(empty($info["Cheats"])){
                               $playerInfoForm->setContent(TextFormat::GREEN . "This player is good and has not been logged!");
                           } else {
                               $playerInfoForm->setContent(TextFormat::RED . "Current Violations: " . TextFormat::YELLOW . "{$info["Current Violations"]}\n" . TextFormat::RED . "Total Violations: " . TextFormat::YELLOW . "{$info["Total Violations"]}\n" . TextFormat::RED . "Detected Cheats: \n" . TextFormat::YELLOW . implode("\n", $info["Cheats"]));
                           }
                           $playerInfoForm->addButton(TextFormat::BOLD . TextFormat::GREEN . "OK");
                           $playerInfoForm->sendToPlayer($player);
                       }
                   });
                   $currentId = 0;
                   foreach(Server::getInstance()->getOnlinePlayers() as $person){
                       $currentViolations = ViolationHandler::getCurrentViolations($person->getName());
                       if($currentViolations <= 10){
                           $form->addButton(TextFormat::BOLD . TextFormat::GREEN . "{$person->getName()}");
                       } elseif($currentViolations > 10 && $currentViolations <= 30){
                           $form->addButton(TextFormat::BOLD . TextFormat::YELLOW . "{$person->getName()}");
                       } elseif($currentViolations > 30){
                           $form->addButton(TextFormat::BOLD . TextFormat::RED . "{$person->getName()}");
                       }
                       $this->ids[$currentId] = $person->getName();
                       $currentId++;
                   }
                   $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . "AntiCheat Logs");
                   $form->setContent(TextFormat::YELLOW . "All of the players online are in this log. Having a green button means the player has less than or has 10 violations. A yellow name means more than 10 violations but less than or 30 violations. A red name means the player has more than 30 violations.");
                   $form->sendToPlayer($sender);
                   break;
           }
       }
    }

}