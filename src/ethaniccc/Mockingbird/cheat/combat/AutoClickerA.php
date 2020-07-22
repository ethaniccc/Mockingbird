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

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class AutoClickerA extends Cheat{

    private $lastClick = [];
    private $speeds = [];
    private $deviations = [];
    private $badDeviations = [];

    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        $name = $player->getName();
        if($packet instanceof InventoryTransactionPacket){
            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
                $this->clickCheck($event->getPlayer());
            }
        } elseif($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE){
                $this->clickCheck($event->getPlayer());
            }
        }
    }

    private function clickCheck(Player $player){
        $name = $player->getName();

        if(!isset($this->lastClick[$name])){
            $this->lastClick[$name] = $this->getServer()->getTick();
            return;
        }
        if(!isset($this->suspicionLevel[$name])){
            $this->suspicionLevel[$name] = 0;
        }

        $tick = $this->lastClick[$name];
        $currentTick = $this->getServer()->getTick();
        $this->lastClick[$name] = $this->getServer()->getTick();

        $speed = ($currentTick - $tick) * 50;
        if($currentTick - $tick > 5){
            $this->lastClick[$name] = $this->getServer()->getTick();
            return;
        }
        if(!isset($this->speeds[$name])){
            $this->speeds[$name] = [];
        }
        array_push($this->speeds[$name], $speed);
        $averageSpeed = array_sum($this->speeds[$name]) / count($this->speeds[$name]);

        $deviation = abs($speed - $averageSpeed);
        if(!isset($this->deviations[$name])){
            $this->deviations[$name] = [];
        }
        array_push($this->deviations[$name], $deviation);
        $averageDeviation = array_sum($this->deviations[$name]) / count($this->deviations[$name]);
        if($averageDeviation < 5){
            if(!isset($this->badDeviations[$name])){
                $this->badDeviations[$name] = [];
            }
            array_push($this->badDeviations[$name], $averageDeviation);
        }
        if(count($this->badDeviations[$name]) >= 25){
            $this->suspicionLevel[$name] += 1;
            if($this->suspicionLevel[$name] >= 5.5){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                $this->suspicionLevel[$name] = 1;
            }
        } else {
            $this->suspicionLevel[$name] *= 0.75;
        }
        if(count($this->speeds[$name]) >= 50){
            unset($this->speeds[$name]);
        }
        if(count($this->deviations[$name]) >= 50){
            unset($this->deviations[$name]);
            unset($this->badDeviations[$name]);
        }
    }

}