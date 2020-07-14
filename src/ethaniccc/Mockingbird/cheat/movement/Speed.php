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
use pocketmine\block\Air;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\Player;

class Speed extends Cheat implements StrictRequirements{

    private $suspicionLevel = [];

    private $lastPosition = [];
    private $lastMove = [];

    private $wasPreviouslyInAir = [];
    private $previouslyJumped = [];
    private $previouslyHadEffect = [];
    private $previouslyOnIce = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setRequiredTPS(19.5);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();
        if($packet instanceof MovePlayerPacket){
            if($player->getAllowFlight() || $player->isFlying()){
                return;
            }
            if(!isset($this->lastPosition[$name])){
                $this->lastPosition[$name] = $player->asVector3();
                return;
            }
            if(isset($this->lastMove[$name])){
                if($this->getServer()->getTick() - $this->lastMove[$name] >= 2){
                    $this->lastMove[$name] = $this->getServer()->getTick();
                    $this->lastPosition[$name] = $packet->position;
                    return;
                }
            }
            $distance = $packet->position->distance($this->lastPosition[$name]);
            if($this->previouslyHadEffect($name)){
                return;
            }
            if($this->previouslyOnIce($name)){
                return;
            }
            if(!$player->getLevel()->getBlock($player->asVector3()->add(0, 2, 0)) instanceof Air){
                return;
            }
            if(!$player->isOnGround()){
                $expectedDistance = 0.785;
                $this->wasPreviouslyInAir[$name] = $this->getServer()->getTick();
            } else {
                if($this->wasPreviouslyInAir($name) || $this->recentlyJumped($name)){
                    $expectedDistance = 0.785;
                } else {
                    $expectedDistance = 0.3;
                }
            }
            if($this->onIce($player)){
                $expectedDistance *= (4 / 3);
                $this->previouslyOnIce[$name] = $this->getServer()->getTick();
            }
            if($player->getEffect(1) !== null){
                $effectLevel = $player->getEffect(1)->getEffectLevel() + 1;
                $expectedDistance *= 1 + (0.2 * $effectLevel);
                $this->previouslyHadEffect[$name] = $this->getServer()->getTick();
            }
            if($distance > $expectedDistance){
                $this->addSuspicion($name);
                if($this->suspicionLevel[$name] >= 3){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                }
            } else {
                $this->lowerSuspicion($name);
            }
            $this->lastMove[$name] = $this->getServer()->getTick();
            $this->lastPosition[$name] = $packet->position;
        }
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $name = $event->getPlayer()->getName();
        $this->previouslyJumped[$name] = $this->getServer()->getTick();
    }

    private function wasPreviouslyInAir(string $name) : bool{
        return isset($this->wasPreviouslyInAir[$name]) ? $this->getServer()->getTick() - $this->wasPreviouslyInAir[$name] <= 5 : false;
    }

    private function recentlyJumped(string $name) : bool{
        return isset($this->previouslyJumped[$name]) ? $this->getServer()->getTick() - $this->previouslyJumped[$name] <= 1 : false;
    }

    private function previouslyHadEffect(string $name) : bool{
        return isset($this->previouslyHadEffect[$name]) ? $this->getServer()->getTick() - $this->previouslyHadEffect[$name] <= 1 : false;
    }

    private function previouslyOnIce(string $name) : bool{
        return isset($this->previouslyOnIce[$name]) ? $this->getServer()->getTick() - $this->previouslyOnIce[$name] <= 10 : false;
    }

    private function addSuspicion(string $name) : void{
        if(!isset($this->suspicionLevel[$name])){
            $this->suspicionLevel[$name] = 0;
        }
        ++$this->suspicionLevel[$name];
    }

    private function lowerSuspicion(string $name, float $multiplier = 0.75) : void{
        isset($this->suspicionLevel[$name]) ? $this->suspicionLevel[$name] *= $multiplier : $this->suspicionLevel[$name] = 0;
    }

    private function onIce(Player $player) : bool{
        return $player->isOnGround() ? in_array($player->getLevel()->getBlock($player->asVector3()->subtract(0, 0.5, 0))->getId(), [ItemIds::ICE, ItemIds::PACKED_ICE, ItemIds::FROSTED_ICE]) : in_array($player->getLevel()->getBlock($player->asVector3()->subtract(0, 1.25, 0))->getId(), [ItemIds::ICE, ItemIds::PACKED_ICE, ItemIds::FROSTED_ICE]);
    }

}