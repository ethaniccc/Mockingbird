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

use ethaniccc\Mockingbird\cheat\StrictRequirments;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

class NoSlowdown extends Cheat implements StrictRequirments{

    private $startedEatingTick = [];
    private $lastMovedTick = [];

    private $suspicionLevel = [];

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
            if(!$player->isUsingItem()){
                unset($this->startedEatingTick[$name]);
            }
            if($distance > 0.165){
                if(!isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] = 0;
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] >= 2){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->suspicionLevel[$name] = 0;
                }
            } else {
                if(isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] *= 0.75;
            }
        }
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $name = $event->getPlayer()->getName();
        if($packet instanceof ActorEventPacket){
            $action = $packet->event;
            switch($action){
                case ActorEventPacket::EATING_ITEM:
                    if(!isset($this->startedEatingTick[$name])) $this->startedEatingTick[$name] = $this->getServer()->getTick();
                    break;
            }
        }
    }

    public function onCompleteEating(PlayerItemConsumeEvent $event) : void{
        $name = $event->getPlayer()->getName();
        unset($this->startedEatingTick[$name]);
    }

    private function playerIsEating(Player $player) : bool{
        if(isset($this->startedEatingTick[$player->getName()])){
            if($this->getServer()->getTick() - $this->startedEatingTick[$player->getName()] >= 25) unset($this->startedEatingTick[$player->getName()]);
        }
        return isset($this->startedEatingTick[$player->getName()]) ? $this->getServer()->getTick() - $this->startedEatingTick[$player->getName()] >= 15 : false;
    }

}