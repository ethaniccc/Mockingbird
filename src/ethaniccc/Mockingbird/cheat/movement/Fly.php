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

use ethaniccc\Mockingbird\cheat\StrictRequirements;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\Air;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\Player;

class Fly extends Cheat implements StrictRequirements{

    private $ticksOffGround = [];
    private $wasDamaged = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->getAllowFlight() && $player->isFlying()){
            return;
        }

        $distance = LevelUtils::getMoveDistance($event->getTo()->asVector3(), $event->getFrom()->asVector3(), LevelUtils::MODE_Y);
        if($distance > 1){
            // Player is probably falling.
            if(isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
            return;
        }
        $blocksAround = LevelUtils::getSurroundingBlocks($player);
        $continue = true;
        foreach($blocksAround as $block){
            if(!$block instanceof Air){
                $continue = false;
            }
        }
        if($continue && !$player->isOnGround() && !$this->wasPreviouslyHit($name)){
            if(!isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
            $this->ticksOffGround[$name] += 1;
            if($this->ticksOffGround[$name] > 60){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        } else {
            if(isset($this->ticksOffGround[$name])){
                $this->ticksOffGround[$name] = 0;
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damaged = $event->getEntity();
        if($damaged instanceof Player){
            $this->wasDamaged[$damaged->getName()] = $this->getServer()->getTick();
        }
    }

    public function onQuit(PlayerQuitEvent $event) : void{
        unset($this->ticksOffGround[$event->getPlayer()->getName()]);
    }

    public function toggleFly(PlayerToggleFlightEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isFlying()){
            if(!$player->getAllowFlight()){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                $this->punish($name);
            }
        }
    }

    private function wasPreviouslyHit(string $name) : bool{
        return isset($this->wasDamaged[$name]) ? $this->getServer()->getTick() - $this->wasDamaged[$name] <= 10 : false;
    }

}