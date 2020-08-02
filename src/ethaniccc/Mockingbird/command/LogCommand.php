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

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\cheat\ViolationHandler;
use ethaniccc\Mockingbird\Mockingbird;
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

    /** @var string */
    private $mode = "normal";

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setPermission($this->getPlugin()->getConfig()->get("log_permission"));
        switch($this->getPlugin()->getConfig()->get("log_command_type")){
            case "normal":
                $this->setDescription("Get the AntiCheat logs of a player!");
                $this->setUsage(TextFormat::RED . "/logs <player>");
                break;
            case "UI":
            default:
                $this->setDescription("Get the AntiCheat logs of players!");
                $this->setUsage(TextFormat::RED . "/logs");
                $this->mode = "UI";
                break;
        }
    }

    /**
     * @return Plugin
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            switch($this->mode){
                case "normal":
                    if(!isset($args[0])){
                        $sender->sendMessage($this->getUsage());
                        return;
                    }
                    $player = Server::getInstance()->getPlayer($args[0]);
                    if($player !== null){
                        $name = $player->getName();
                        $playerData = ViolationHandler::getPlayerData($name);
                        if($playerData["Total Violations"] == 0){
                            $sender->sendMessage(TextFormat::GREEN . "The specified player has no logs!");
                        } else {
                            $averageTPS = $playerData["Average TPS"];
                            $currentViolations = $playerData["Current Violations"];
                            $totalViolations = $playerData["Total Violations"];
                            $cheats = $playerData["Cheats"];
                            $message = TextFormat::RED . "====----====\n";
                            $message .= TextFormat::RED . "Player: " . TextFormat::GRAY . "$name\n";
                            $message .= TextFormat::RED . "Average TPS: " . TextFormat::GRAY . "$averageTPS\n";
                            $message .= TextFormat::RED . "Current Violations: " . TextFormat::GRAY . "$currentViolations\n";
                            $message .= TextFormat::RED . "Total Violations: " . TextFormat::GRAY . "$totalViolations\n";
                            $message .= TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats);
                            $message .= TextFormat::RED . "\n====----====";
                            $sender->sendMessage($message);
                        }
                    } else {
                        $name = $args[0];
                        $playerData = ViolationHandler::getPlayerData($name);
                        if($playerData["Total Violations"] == 0){
                            $sender->sendMessage(TextFormat::GREEN . "The specified player has no logs!");
                        } else {
                            $averageTPS = $playerData["Average TPS"];
                            $currentViolations = $playerData["Current Violations"];
                            $totalViolations = $playerData["Total Violations"];
                            $cheats = $playerData["Cheats"];
                            $message = TextFormat::RED . "====----====\n";
                            $message .= TextFormat::RED . "Player: " . TextFormat::GRAY . "$name\n";
                            $message .= TextFormat::RED . "Average TPS: " . TextFormat::GRAY . "$averageTPS\n";
                            $message .= TextFormat::RED . "Current Violations: " . TextFormat::GRAY . "$currentViolations\n";
                            $message .= TextFormat::RED . "Total Violations: " . TextFormat::GRAY . "$totalViolations\n";
                            $message .= TextFormat::RED . "Cheats Detected: " . TextFormat::GRAY . implode(", ", $cheats);
                            $message .= TextFormat::RED . "\n====----====";
                            $sender->sendMessage($message);
                        }
                    }
                    break;
                case "UI":
                default:
                    if (!$sender instanceof Player) {
                        $sender->sendMessage(TextFormat::RED . "You must run this command as a player!");
                        return;
                    }
                    $form = new SimpleForm(function (Player $player, $data = null) {
                        if ($data !== null) {
                            if (!isset($this->ids[$data])) {
                                $player->sendMessage(TextFormat::RED . "Something went wrong!");
                                return;
                            }
                            $playerInfoForm = new SimpleForm(function (Player $player, $data = null) {
                            });
                            $playerInfoForm->setTitle(TextFormat::BOLD . "{$this->ids[$data]}'s Logs");
                            $info = ViolationHandler::getPlayerData($this->ids[$data]);
                            if (empty($info["Cheats"])) {
                                $playerInfoForm->setContent(TextFormat::GREEN . "This player is good and has not been logged!");
                            } else {
                                $playerInfoForm->setContent(TextFormat::RED . "Average TPS: " . TextFormat::YELLOW . "{$info["Average TPS"]}\n" . TextFormat::RED . "Current Violations: " . TextFormat::YELLOW . "{$info["Current Violations"]}\n" . TextFormat::RED . "Total Violations: " . TextFormat::YELLOW . "{$info["Total Violations"]}\n" . TextFormat::RED . "Detected Cheats: \n" . TextFormat::YELLOW . implode("\n", $info["Cheats"]));
                            }
                            $playerInfoForm->addButton(TextFormat::BOLD . TextFormat::GREEN . "OK");
                            $playerInfoForm->sendToPlayer($player);
                        }
                    });
                    $currentId = 0;
                    foreach (Server::getInstance()->getOnlinePlayers() as $person) {
                        $allViolations = ViolationHandler::getAllViolations($person->getName());
                        if ($allViolations <= 10) {
                            $form->addButton(TextFormat::GREEN . "{$person->getName()}");
                        } elseif ($allViolations > 10 && $allViolations <= 30) {
                            $form->addButton(TextFormat::YELLOW . "{$person->getName()}");
                        } elseif ($allViolations > 30 && $allViolations < 100) {
                            $form->addButton(TextFormat::RED . "{$person->getName()}");
                        } else {
                            $form->addButton(TextFormat::DARK_RED . "{$person->getName()}");
                        }
                        $this->ids[$currentId] = $person->getName();
                        $currentId++;
                    }
                    //Attempt to check for offline players
                    foreach(ViolationHandler::getSaveData() as $offlineName => $data){
                        if(!in_array($offlineName, $this->ids)){
                            $this->ids[$currentId] = $offlineName;
                            $currentId++;
                        }
                    }
                    $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . "AntiCheat Logs");
                    $form->setContent(TextFormat::WHITE . "All of the players online are in this log.\n" . TextFormat::GREEN . "A green name means the player has less than or has 10 total violations.\n" . TextFormat::YELLOW . "A yellow name means more than 10 violations but less than or total 30 violations.\n" . TextFormat::RED . "A red name means the player has more than 30 total violations and less than 100 total violations.\n" . TextFormat::DARK_RED . "A dark red name means more than or 100 total violations.");
                    $form->sendToPlayer($sender);
                    break;
            }
        }
    }

}