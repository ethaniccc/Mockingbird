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
use pocketmine\block\BlockIds;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\TextFormat;
use pocketmine\block\Ice;
use pocketmine\block\PackedIce;

class Speed extends Cheat implements StrictRequirements{

    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isCreative()){
            return;
        }
        if($player->isFlying()){
            return;
        }

        $distX = $event->getTo()->getX() - $event->getFrom()->getX();
        $distZ = $event->getTo()->getZ() - $event->getFrom()->getZ();
        $distanceSquared = ($distX * $distX) + ($distZ * $distZ);
        $distance = sqrt($distanceSquared);

        $blocksPerSecond = round($distance * 20, 1, PHP_ROUND_HALF_UP);
        //$this->getServer()->broadcastMessage("BPS: $blocksPerSecond");
        if(!isset($this->suspicionLevel[$name])){
            $this->suspicionLevel[$name] = 0;
            return;
        }

        if($player->isOnGround()){
            $expectedMaxSpeed = 5.7;
        } else {
            $expectedMaxSpeed = 7.6;
        }
        if($player->getLevel()->getBlock($player->asVector3()->add(0, 2, 0))->getId() !== 0){
            return;
        }
        if(in_array($player->getLevel()->getBlock($player->asVector3()->subtract(0, 1, 0))->getId(), [BlockIds::ICE, BlockIds::FROSTED_ICE, BlockIds::PACKED_ICE])){
            $expectedMaxSpeed *= (5 / 3);
        }
        if($player->getEffect(1) !== null){
            $level = $player->getEffect(1)->getEffectLevel() + 1;
            $multiplier = 1 + ($level * 0.2);
            $expectedMaxSpeed *= $multiplier;
        }

        if($blocksPerSecond > $expectedMaxSpeed){
            if($blocksPerSecond >= $expectedMaxSpeed * 1.45 && $blocksPerSecond <= $expectedMaxSpeed * 2.25){
                // Spike???
                $this->suspicionLevel[$name] *= 0.9;
                return;
            }
            $this->suspicionLevel[$name] += 1;
            if($this->suspicionLevel[$name] >= 5){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                $this->suspicionLevel[$name] *= 0.5;
            }
        } else {
            $this->suspicionLevel[$name] *= 0.75;
        }
    }

}