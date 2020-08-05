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

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Consumable;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;


class FastEat extends Cheat implements StrictRequirements{

    private $startEatTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(20);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof ActorEventPacket){
            if($packet->event === ActorEventPacket::EATING_ITEM){
                if(!isset($this->startEatTick[$name])){
                    $this->startEatTick[$name] = $this->getServer()->getTick();
                }
            }
        }
    }

    public function completeEat(PlayerItemConsumeEvent $event){
        $item = $event->getItem();
        $player = $event->getPlayer();
        $name = $player->getName();
        if($item instanceof Consumable){
            if($this->playerIsEating($player)){
                $timeDiff = $this->getServer()->getTick() - $this->startEatTick[$name];
                if($timeDiff < 20){
                    $event->setCancelled();
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->debugNotify("$name finished eating a consumable within $timeDiff ticks, at least 20 ticks were expected.");
                }
            }
            unset($this->startEatTick[$name]);
        }
    }

    private function playerIsEating(Player $player) : bool{
        if(isset($this->startEatTick[$player->getName()])){
            if($this->getServer()->getTick() - $this->startEatTick[$player->getName()] > 25) unset($this->startEatTick[$player->getName()]);
        }
        return isset($this->startEatTick[$player->getName()]) ? $this->getServer()->getTick() - $this->startEatTick[$player->getName()] <= 25 : false;
    }

}