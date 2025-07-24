<?php

namespace ethaniccc\Mockingbird\commands;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class UserLogsCommand extends Command implements PluginOwned{
	private Mockingbird $plugin;

	public function __construct(Mockingbird $plugin){
		$this->plugin = $plugin;
		parent::__construct('mblogs', 'Get the anti-cheat logs of a user', '/mblogs <player>', []);
		$this->setPermission('mockingbird.logs');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void{
		if($this->testPermission($sender)){
			$selected = $args[0] ?? null;
			if($selected !== null){
				$user = UserManager::getInstance()->getUserByName($selected);
				if($user !== null){
					$message = TextFormat::BOLD . TextFormat::GOLD . "{$user->player->getName()}'s logs:\n" . TextFormat::RESET;
					$has = 0;
					foreach($user->violations as $check => $violationCount){
						$violationCount = round($violationCount, 3);
						if($violationCount >= 1){
							$has++;
							$c = $user->detections[$check];
							$message .= $c->punishable ? TextFormat::RED . $check . TextFormat::GRAY . ' => ' . TextFormat::GOLD . "($violationCount / {$c->maxVL}) " . TextFormat::GRAY . '@ ' . $c->probabilityColor($c->getCheatProbability()) . TextFormat::RESET . "\n" : TextFormat::RED . $check . TextFormat::GRAY . ' => ' . TextFormat::GOLD . "($violationCount) " . TextFormat::GRAY . '@ ' . $c->probabilityColor($c->getCheatProbability()) . TextFormat::RESET . "\n";
						}
					}
					if($has === 0){
						$message = TextFormat::GREEN . "{$user->player->getName()} has no violations\n";
					}
					$sender->sendMessage($message);
				}else{
					$sender->sendMessage($this->getOwningPlugin()->getPrefix() . TextFormat::RED . " User $selected could not be found");
				}
			}else{
				$sender->sendMessage($this->getOwningPlugin()->getPrefix() . TextFormat::RED . ' ' . $this->getUsage());
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