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

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\inventory\InventoryTransactionEvent;

class ChestStealer extends Cheat implements StrictRequirements{

    private $lastTransaction = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(19.5);
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
                $event->setCancelled();
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