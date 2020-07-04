<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\inventory\InventoryTransactionEvent;

class ChestStealer extends Cheat{

    private $lastTransaction = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $name = $player->getName();

        if(!isset($this->lastTransaction[$name])){
            $this->lastTransaction[$name] = microtime(true);
            return;
        }

        $timeDiff = microtime(true) - $this->lastTransaction[$name];
        if($timeDiff < 0.001){
            if(!isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] = 0;
            }
            $this->suspicionLevel[$name] += 1;
            if($this->suspicionLevel[$name] >= 3.5){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                $this->suspicionLevel[$name] *= 0.5;
            }
        } else {
            if(isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] *= 0.5;
            }
        }
        $this->lastTransaction[$name] = microtime(true);
    }

}