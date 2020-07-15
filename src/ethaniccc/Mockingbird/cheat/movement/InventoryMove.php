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

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;

class InventoryMove extends Cheat implements StrictRequirements, Blatant{

    private $lastMoveTick = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(5);
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
        $transaction = $event->getTransaction();
        $player = $transaction->getSource();
        $name = $player->getName();

        if(!isset($this->lastMoveTick[$name])){
            return;
        }

        $timeDiff = $this->getServer()->getTick() - $this->lastMoveTick[$name];
        if($timeDiff == 0){
            // If the player's motion is not being set, for example, when a player
            // is hit and takes knockback.
            if($player->getMotion()->x == 0 && $player->getMotion()->z == 0){
                if(!isset($this->suspicionLevel[$name])){
                    $this->suspicionLevel[$name] = 0;
                }
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] > 5){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->suspicionLevel[$name] = 0;
                }
            }
        } else {
            if(isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] *= 0.25;
            }
        }
    }

    public function onMove(PlayerMoveEvent $event) : void{
        if($event->getTo()->getX() - $event->getFrom()->getX() == 0 && $event->getTo()->getZ() - $event->getFrom()->getZ() == 0){
            return;
        }

        $distX = $event->getTo()->getX() - $event->getFrom()->getX();
        $distZ = $event->getTo()->getZ() - $event->getFrom()->getZ();
        $distanceSquared = ($distX * $distX) + ($distZ * $distZ);
        $distance = sqrt($distanceSquared);
        if($distance < 0.1){
            return;
        }
        $this->lastMoveTick[$event->getPlayer()->getName()] = $this->getServer()->getTick();
    }

}