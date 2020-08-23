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

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\ScreenshareTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScreenshareCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;
    /** @var array */
    public $previousPosition, $screenshareTask = [];

    public function __construct(string $name, Mockingbird $plugin, string $description = "", string $usageMessage = null, array $aliases = []){
        parent::__construct($name, $description, $usageMessage, $aliases);
        $this->plugin = $plugin;
        $this->setDescription("Get a player's point of view with the Screenshare command!");
        $this->setPermission($this->getPlugin()->getConfig()->get("screenshare_permission"));
    }

    /**
     * @return Mockingbird
     * PHPStan is prob gonna kill me for ^ lol
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return mixed|void
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            if(!$sender instanceof Player){
                $sender->sendMessage(TextFormat::RED . "You must run this command as a player!");
            } else {
                $name = $sender->getName();
                if(!isset($args[0])){
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You need to specify a player to screenshare.");
                    return;
                }
                if(isset($args[0])){
                    if($args[0] === "end"){
                        if(!isset($this->screenshareTask[$name])){
                            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "There is no screenshare to cancel.");
                            return;
                        }
                        $this->getPlugin()->getScheduler()->cancelTask($this->screenshareTask[$name]->getTaskId());
                        $target = $this->screenshareTask[$name]->getTarget();
                        if($target instanceof Player){
                            $sender->showPlayer($target);
                            foreach(Server::getInstance()->getOnlinePlayers() as $other){
                                $other->showPlayer($sender);
                            }
                        }
                        $sender->teleport($this->previousPosition[$name]);
                        unset($this->previousPosition[$name]);
                        unset($this->screenshareTask[$name]);
                    } else {
                        $targetPlayer = Server::getInstance()->getPlayer($args[0]);
                        if($targetPlayer instanceof Player){
                            if(isset($this->screenshareTask[$name])){
                                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You already are screensharing another player!");
                                return;
                            }
                            if($sender->getName() === $targetPlayer->getName()){
                                $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "You cannot screenshare yourself!");
                                return;
                            }
                            $name = $targetPlayer->getName();
                            $task = new ScreenshareTask($this->getPlugin(), $sender->getName(), $name, $this);
                            $this->getPlugin()->getScheduler()->scheduleRepeatingTask($task, 1);
                            $this->screenshareTask[$sender->getName()] = $task;
                            $this->previousPosition[$sender->getName()] = $sender->asVector3();
                            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::GREEN . "You are now screensharing $name. If you'd like to end your screenshare, do /mbscreenshare end");
                        } else {
                            $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . "{$args[0]} was not found on the server.");
                        }
                    }
                }
            }
        }
    }

}