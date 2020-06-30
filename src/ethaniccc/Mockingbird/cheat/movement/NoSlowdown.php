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

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\item\Consumable;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemIds;

class NoSlowdown extends Cheat{

    private $startedEatingTick = [];
    private $lastMovedTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isFlying()) return;
        if($player->getEffect(1) !== null){
            if($player->getEFfect()->getEffectLevel() > 10) return;
        }

        if(!isset($this->lastMovedTick[$name])){
            $this->lastMovedTick[$name] = $this->getServer()->getTick();
        } else {
            if($this->getServer()->getTick() - $this->lastMovedTick[$name] > 1){
                $this->lastMovedTick[$name] = $this->getServer()->getTick();
                return;
            } else {
                $this->lastMovedTick[$name] = $this->getServer()->getTick();
            }
        }

        $from = $event->getFrom();
        $to = $event->getTo();

        $distX = ($to->x - $from->x);
        $distZ = ($to->z - $from->z);

        $distanceSquared = abs(($distX * $distX) + ($distZ * $distZ));
        $distance = sqrt($distanceSquared);

        if($player->isUsingItem()){
            $item = $player->getInventory()->getItemInHand();
            if($item instanceof Consumable){
                if($player->getFood() == $player->getMaxFood() && !in_array($item->getId(), [ItemIds::GOLDEN_APPLE, ItemIds::ENCHANTED_GOLDEN_APPLE, ItemIds::GOLDEN_CARROT])) return;
                if(!isset($this->startedEatingTick[$name])){
                    $this->startedEatingTick[$name] = $this->getServer()->getTick();
                    return;
                } else {
                    if($this->getServer()->getTick() - $this->startedEatingTick[$name] < 20){
                        return;
                    }
                }
                if($distance > 0.165){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
            }
        } else {
            if(isset($this->startedEatingTick[$name])) unset($this->startedEatingTick[$name]);
        }
    }

}