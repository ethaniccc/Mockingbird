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

namespace ethaniccc\Mockingbird\tasks;

use ethaniccc\Mockingbird\command\ScreenshareCommand;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ScreenshareTask extends Task{

    /** @var Mockingbird */
    private $plugin;
    /** @var string */
    private $player, $target;
    /** @var ScreenshareCommand */
    private $command;

    public function __construct(Mockingbird $plugin, string $player, string $target, ScreenshareCommand $command){
        $this->plugin = $plugin;
        $this->command = $command;
        $this->player = $player;
        $this->target = $target;
    }

    public function onRun(int $currentTick){
        $target = Server::getInstance()->getPlayer($this->target);
        $player = Server::getInstance()->getPlayer($this->player);
        if($target === null || $player === null){
            $id = $this->getTaskId();
            $this->plugin->getScheduler()->cancelTask($id);
            if($player !== null){
                $player->sendMessage($this->plugin->getPrefix() . TextFormat::RED . "We have lost the player and your screenshare has been ended.");
                $player->teleport($this->command->previousPosition[$player->getName()]);
            }
            unset($this->command->previousPosition[$this->player]);
            unset($this->command->screenshareTask[$this->player]);
        } else {
            $player->hidePlayer($target);
            foreach(Server::getInstance()->getOnlinePlayers() as $other){
                $other->hidePlayer($player);
            }
            $player->sendPosition($target->asVector3(), $target->getYaw(), $target->getPitch(), MovePlayerPacket::MODE_TELEPORT);
        }
    }

   public function getPlayer() : ?Player{
        return Server::getInstance()->getPlayer($this->player);
   }

   public function getTarget() : ?Player{
        return Server::getInstance()->getPlayer($this->target);
   }

}