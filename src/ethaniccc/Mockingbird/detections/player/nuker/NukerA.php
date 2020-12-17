<?php

namespace ethaniccc\Mockingbird\detections\player\nuker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class NukerA
 * @package ethaniccc\Mockingbird\detections\player\nuker
 * NukerA checks if the user is breaking too many blocks within a tick.
 */
class NukerA extends Detection{

    private $blocks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user) : void{
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK){
            ++$this->blocks;
        } elseif($packet instanceof PlayerAuthInputPacket){
            // PlayerAuthInputPacket on top
            if($this->blocks >= (int) $this->getSetting("max_blocks")){
                $this->fail($user, "blocks={$this->blocks}");
            } else {
                if($this->blocks > 0){
                    $this->reward($user, 0.99);
                }
            }
            $this->blocks = 0;
        }
    }

}