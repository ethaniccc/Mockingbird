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
~ Made by @Blackjack200 </3
Github: https://github.com/ethaniccc/Mockingbird
*/

namespace ethaniccc\Mockingbird\cheat\movement;


use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\BlockUtils;
use pocketmine\event\player\PlayerMoveEvent;

class NoClip extends Cheat {
	public function onPlayerMove(PlayerMoveEvent $event) : void {
		$player = $event->getPlayer();
		
		if($player->isSpectator()) return;
		
		$name = $player->getName();
		
		$head = $player->getLevel()->getBlock($player->add(0 , 1 , 0));
		$body = $player->getLevel()->getBlock($player);
		
		$fromBlock = $head->getLevelNonNull()->getBlock($head);
		$toBlock = $body->getLevelNonNull()->getBlock($body);
		
		$fromBlockAABB = $fromBlock->getBoundingBox();
		$toBlockAABB = $toBlock->getBoundingBox();
		
		if($fromBlockAABB === null || $toBlockAABB === null) {
			return;
		}
		
		if(BlockUtils::canClip($fromBlock) || BlockUtils::canClip($toBlock)) {
			return;
		}
		
		if(!($toBlockAABB->isVectorInside($player) || $fromBlockAABB->isVectorInside($player))) {
			return;
		}
		
		$event->setCancelled();
		$this->fail($player , "$name tried NoClip.");
		//TODO FALSE DETECTION WHEN LAG (Chunk Cache)
	}
}