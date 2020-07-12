<?php

namespace ethaniccc\Mockingbird\command;

use ethaniccc\Mockingbird\cheat\ViolationHandler;
use ethaniccc\Mockingbird\Mockingbird;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\command\CommandSender;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ReportCommand extends Command implements PluginIdentifiableCommand{

    private const COMMAND_COOLDOWN = 60;

    /** @var Mockingbird */
    private $plugin;

    /** @var array */
    private $ids = [];
    /** @var array */
    private $cheats = [];
    /** @var array */
    private $cooldown = [];

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
    }

    /**
     * @return Mockingbird
     * I wonder when PMMP will use PHP 7.4 so I can
     * change the return type and stop getting
     * PHPStan errors **insert :thinkies:**
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage(TextFormat::RED . "You must run this command as a player!");
            return;
        }
        if(!isset($this->cooldown[$sender->getName()])){
            $this->cooldown[$sender->getName()] = microtime(true);
        } else {
            if(microtime(true) - $this->cooldown[$sender->getName()] < self::COMMAND_COOLDOWN){
                $timeRemaining = round(self::COMMAND_COOLDOWN - (microtime(true) - $this->cooldown[$sender->getName()]), 1);
                $sender->sendMessage(TextFormat::RED . "You must wait {$timeRemaining} seconds before using this command again!");
                return;
            } else {
                $this->cooldown[$sender->getName()] = microtime(true);
            }
        }
        $form = new SimpleForm(function(Player $player, $data = null){
            if(empty($this->ids)){
                return;
            }
            if(!isset($this->ids[$data])){
                $player->sendMessage(TextFormat::RED . "Something went wrong!");
                return;
            }
            $selectedName = $this->ids[$data];
            $newForm = new CustomForm(function(Player $player, $data = null) use($selectedName){
                if($data === null){
                    return;
                }
                $cheatChosen = $this->cheats[$data[0]];
                $currentPlayerCheats = ViolationHandler::getCheatsViolatedFor($selectedName);
                if(!in_array($cheatChosen, $currentPlayerCheats) && ViolationHandler::getAllViolations($selectedName) < 10){
                    $player->sendMessage(TextFormat::RED . "$selectedName has not been detected for $cheatChosen, but we will send a message to you if they trigger the $cheatChosen detection.");
                    $this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($player, $cheatChosen, $selectedName) : void{
                        if(!in_array($cheatChosen, ViolationHandler::getCheatsViolatedFor($selectedName)) && ViolationHandler::getAllViolations($selectedName) < 10){
                            $player->sendMessage(TextFormat::RED . "We could not find any evidence of this player flagging a $cheatChosen detection.");
                        } else {
                            $player->sendMessage(TextFormat::GREEN . "$selectedName has failed a check for $cheatChosen and a staff member will be asked to check on the situation!");
                            $this->alertStaffOfPossibleCheater($selectedName, $cheatChosen);
                        }
                    }), (20 * 30));
                } else {
                    $player->sendMessage(TextFormat::GREEN . "$selectedName previously failed a check for $cheatChosen and a staff member will be asked to check on the situation!");
                    $this->alertStaffOfPossibleCheater($selectedName, $cheatChosen);
                }
            });
            $cheats = $this->getPlugin()->getEnabledModules();
            unset($this->cheats);
            $this->cheats = [];
            foreach($cheats as $module){
                array_push($this->cheats, $module->getName());
            }
            $newForm->setTitle(TextFormat::BOLD . TextFormat::GOLD . "Report");
            $newForm->addDropdown("Hack To Report", $this->cheats);
            $newForm->sendToPlayer($player);
        });
        $form->setTitle(TextFormat::BOLD . TextFormat::GOLD . "Report");
        $currentId = 0;
        unset($this->ids);
        $this->ids = [];
        foreach(Server::getInstance()->getOnlinePlayers() as $person){
            if($person->getName() !== $sender->getName()){
                $form->addButton(TextFormat::AQUA . $person->getName());
                $this->ids[$currentId] = $person->getName();
                $currentId++;
            }
        }
        if($currentId === 0){
            // Since form buttons are required...
            $form->setContent(TextFormat::RED . "There are no players to report.");
            $form->addButton(TextFormat::BOLD . TextFormat::GREEN . "OK");
        } else {
            $form->setContent("Choose a player to report.");
        }
        $form->sendToPlayer($sender);
    }

    private function alertStaffOfPossibleCheater(string $name, string $cheat) : void{
        foreach(Server::getInstance()->getOnlinePlayers() as $staff){
            if($staff->hasPermission($this->getPlugin()->getConfig()->get("alert_permission"))){
                $staff->sendMessage($this->getPlugin()->getConfig()->get("prefix") . TextFormat::RESET . TextFormat::RED . " $name has been reported for using unfair advantage ($cheat) - Please check on the situation!");
            }
        }
    }

}