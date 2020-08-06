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

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

class NoSlowdown extends Cheat implements StrictRequirements{

    private $startedEatingTick = [];
    private $lastMovedTick = [];

    private $wasHit = [];

    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{

        if($event->getMode() !== MoveEvent::MODE_NORMAL){
            return;
        }

        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isFlying() || $player->getAllowFlight()) return;
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

        $distance = $event->getDistanceXZ();

        if($this->playerIsEating($player) && !$this->wasRecentlyHit($name)){
            if(!$player->isUsingItem()){
                unset($this->startedEatingTick[$name]);
            }
            if($distance > 0.165){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 2){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->suspicionLevel[$name] = 0;
                }
            } else {
                $this->lowerPreVL($name);
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

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $entity = $event->getEntity();
        if(!$entity instanceof Player){
            return;
        }
        $this->wasHit[$entity->getName()] = $this->getServer()->getTick();
    }

    private function playerIsEating(Player $player) : bool{
        if(isset($this->startedEatingTick[$player->getName()])){
            if($this->getServer()->getTick() - $this->startedEatingTick[$player->getName()] >= 25) unset($this->startedEatingTick[$player->getName()]);
        }
        return isset($this->startedEatingTick[$player->getName()]) ? $this->getServer()->getTick() - $this->startedEatingTick[$player->getName()] >= 15 : false;
    }

    private function wasRecentlyHit(string $name) : bool{
        return isset($this->wasHit[$name]) ? $this->getServer()->getTick() - $this->wasHit[$name] <= 10 : false;
    }

}