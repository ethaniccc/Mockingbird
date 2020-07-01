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
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\Consumable;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemIds;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

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
            if($player->getEFfect(1)->getEffectLevel() > 10) return;
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

        if($this->playerIsEating($player)){
            $this->getServer()->broadcastMessage("Eating!");
            if($distance > 0.165){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        }
    }

    public function onEat(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof ActorEventPacket){
            $action = $packet->event;
            switch($action){
                case ActorEventPacket::EATING_ITEM:
                    if(!isset($this->startedEatingTick[$name])) $this->startedEatingTick[$name] = $this->getServer()->getTick();
                    break;
                case ActorEventPacket::ARM_SWING:
                    if(isset($this->startedEatingTick[$name])) unset($this->startedEatingTick[$name]);
                    break;
            }
        }
    }

    public function onCompleteEating(PlayerItemConsumeEvent $event) : void{
        $name = $event->getPlayer()->getName();
        unset($this->startedEatingTick[$name]);
    }

    private function playerIsEating(Player $player) : bool{
        return isset($this->startedEatingTick[$player->getName()]) ? $this->getServer()->getTick() - $this->startedEatingTick[$player->getName()] >= 20 : false;
    }

}