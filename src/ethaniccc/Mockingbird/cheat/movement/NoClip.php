<?php


namespace ethaniccc\Mockingbird\cheat\movement;


use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\block\Cobblestone;
use pocketmine\block\Obsidian;
use pocketmine\block\Stone;
use pocketmine\entity\object\FallingBlock;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\level\LevelLoadEvent;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use SplObjectStorage;

/** @deprecated WIP */
class NoClip extends Cheat {
	/** @var array<string,SplObjectStorage<Vector3>> */
	public $bypassBlocks = [];
	
	public function onLevelLoad(LevelLoadEvent $event) {
		$this->bypassBlocks[$event->getLevelNonNull()->getFolderName()] = new SplObjectStorage();
	}
	
	public function onLevelUnload(LevelUnloadEvent $event) {
		if($event->isCancelled()) {
			return;
		}
		unset($this->bypassBlocks[$event->getLevelNonNull()->getFolderName()]);
	}
	
	public function onPlayerMove(PlayerMoveEvent $event) {
		if(!$event->isCancelled()) {
			$player = $event->getPlayer();
			$blockA = $player->getLevelNonNull()->getBlock($player);
			$blockB = $player->getLevelNonNull()->getBlock($player->add(0 , 1 , 0));
			if(!($blockA->isPassable() || $blockB->isPassable())) {
				/** @var SplObjectStorage $blocks */
				$blocks = $this->bypassBlocks[$player->getLevelNonNull()->getFolderName()];
				if(!($blocks->contains($blockA->asVector3()) || $blocks->contains($blockB->asVector3()))) {
					$this->fail($player , 'tried NoClip');
					$event->setCancelled();
				}
			}
		}
	}
	
	public function onBlockFall(EntityBlockChangeEvent $event) {
		if($event->getEntity() instanceof FallingBlock && !$event->isCancelled()) {
			$block = $event->getTo();
			/** @var SplObjectStorage $blocks */
			$blocks = $this->bypassBlocks[$block->getLevelNonNull()->getFolderName()];
			$blocks->attach($block->asVector3());
		}
	}
	
	public function onBlockForm(BlockFormEvent $event) {
		$block = $event->getNewState();
		if($block instanceof Obsidian or $block instanceof Stone or $block instanceof Cobblestone && !$event->isCancelled()) {
			/** @var SplObjectStorage $blocks */
			$blocks = $this->bypassBlocks[$block->getLevelNonNull()->getFolderName()];
			$blocks->attach($block->asVector3());
		}
	}
	
	public function onBlockBreak(BlockBreakEvent $event) {
		if(!$event->isCancelled()) {
			$block = $event->getBlock();
			/** @var SplObjectStorage $blocks */
			$blocks = $this->bypassBlocks[$block->getLevelNonNull()->getFolderName()];
			$blocks->detach($block->asVector3());
		}
	}
}