<?php

namespace ethaniccc\Mockingbird\packets;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

class BlockPlacePacket extends DataPacket{

    public $player;
    public $item;
    public $blockReplaced;
    public $blockAgainst;

    public function __construct(BlockPlaceEvent $event){
        $this->player = $event->getPlayer();
        $this->item = $event->getItem();
        $this->blockReplaced = $event->getBlockReplaced();
        $this->blockAgainst = $event->getBlockAgainst();
    }

    public function handle(NetworkSession $session): bool{
        return true;
    }

}