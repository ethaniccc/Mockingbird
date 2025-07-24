<?php

namespace ethaniccc\Mockingbird\detections\player\cheststeal;

use ethaniccc\Mockingbird\detections\NopDetection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\block\inventory\DoubleChestInventory;
use pocketmine\event\Event;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class ChestStealerA
 * @package ethaniccc\Mockingbird\detections\player\cheststeal
 * ChestStealerA checks if the user is taking items from a chest too quickly.
 */
class ChestStealerA extends NopDetection{
	public int $transactions = 0;

	public function __construct(string $name, ?array $settings){
		parent::__construct($name, $settings);
	}

	public function handleReceive(DataPacket $packet, User $user) : void{
		if($packet instanceof PlayerAuthInputPacket){
			if($this->transactions > (int) $this->getSetting("max_transactions")){
				$this->fail($user, "transactions={$this->transactions}");
			}else{
				if($this->transactions !== 0){
					$this->reward($user, 0.04);
				}
			}
			$this->transactions = 0;
		}
	}

	public function handleEvent(Event $event, User $user) : void{
		if($event instanceof InventoryTransactionEvent){
			foreach($event->getTransaction()->getInventories() as $inventory){
				if($inventory instanceof ChestInventory || $inventory instanceof DoubleChestInventory){
					// TODO: Account for certain transactions that can false this check.
					$this->transactions++;
					return;
				}
			}
		}
	}
}