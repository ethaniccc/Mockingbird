<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\tasks\KickTask;
use ethaniccc\Mockingbird\user\UserManager;
use ethaniccc\TextCenterFormat\TextCenterFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\entity\Entity;
use pocketmine\entity\Villager;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class UserDebugCommand extends Command implements PluginIdentifiableCommand{

    /** @var Mockingbird */
    private $plugin;

    public function __construct(Mockingbird $plugin){
        $this->plugin = $plugin;
        parent::__construct('mbdebug', 'Get the anti-cheat debug logs of a user and a specified detection', '/mbdebug <player> <detection_name>', []);
        $this->setPermission('mockingbird.debug');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args){
        if($this->testPermission($sender)){
            $selectedUser = $args[0] ?? null;
            $selectedCheat = $args[1] ?? null;
            if($selectedUser === null || $selectedCheat === null){
                $sender->sendMessage($this->getUsage());
            } else {
                $user = UserManager::getInstance()->getUserByName($selectedUser);
                if($selectedUser === '--toggle-debug' && $sender instanceof Player){
                    $user = UserManager::getInstance()->get($sender);
                    $user->debugChannel = $selectedCheat === 'off' ? null : strtolower($selectedCheat);
                    $selectedCheat === 'off' ? $sender->sendMessage('Debug information has been disabled.') : $sender->sendMessage("Debug information for $selectedCheat has been enabled.");;
                    return;
                } elseif($selectedUser === '--spawn-dummy' && $sender instanceof Player){
                    $nbt = Entity::createBaseNBT($sender->asVector3());
                    $dummy = new Villager($sender->getLevelNonNull(), $nbt);
                    $dummy->setMaxHealth(10000);
                    $dummy->setHealth(10000);
                    $dummy->spawnTo($sender);
                    return;
                }
                if($user === null){
                    $sender->sendMessage($this->getPlugin()->getPrefix() . TextFormat::RED . " Could not find the user $selectedUser");
                } else {
                    $selectedCheat = strtolower($selectedCheat);
                    $sender->sendMessage(TextFormat::BOLD . TextFormat::RED . 'DEBUG DATA' . PHP_EOL . TextFormat::RESET . ($user->debugCache[$selectedCheat] ?? 'NO DATA'));
                }
            }
        }
    }

    /**
     * @return Mockingbird
     */
    public function getPlugin(): Plugin{
        return $this->plugin;
    }

}