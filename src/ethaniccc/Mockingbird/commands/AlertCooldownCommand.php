<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class AlertCooldownCommand extends Command implements PluginOwned{
	private Mockingbird $plugin;

	public function __construct(Mockingbird $plugin, string $description = '', string $usageMessage = null, array $aliases = []){
		parent::__construct('mbdelay', $description, $usageMessage, $aliases);
		$this->setDescription('Change your alert cooldown in-game');
		$this->setUsage('/mbdelay <seconds>');
		$this->setPermission('mockingbird.alerts');
		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if($this->testPermission($sender)){
			if(!$sender instanceof Player){
				$sender->sendMessage('This command can only be ran by a player.');
				return;
			}
			$secondsArg = $args[0] ?? null;
			if($secondsArg !== null){
				$intVal = (int) $secondsArg;
				$user = UserManager::getInstance()->get($sender);
				if($user !== null){
					$user->alertCooldown = $intVal;
					$user->sendMessage($this->getOwningPlugin()->getPrefix() . TextFormat::GREEN . ' Your alert cooldown has been set to ' . $secondsArg . ' seconds!');
				}else{
					$sender->sendMessage(TextFormat::RED . 'Something went wrong, try re-logging.');
				}
			}else{
				$sender->sendMessage($this->getUsage());
			}
		}
	}

	/**
	 * @return Mockingbird
	 */
	public function getOwningPlugin() : Plugin{
		return $this->plugin;
	}
}