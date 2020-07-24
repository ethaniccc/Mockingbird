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
use pocketmine\entity\Effect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class FastBreak extends Cheat implements StrictRequirements{

    private $startBreakTick = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof PlayerActionPacket){
            if($packet->action === PlayerActionPacket::ACTION_START_BREAK){
                $this->startBreakTick[$event->getPlayer()->getName()] = microtime(true);
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->getEffect(Effect::HASTE) !== null){
            return;
        }

        if($player->isCreative()){
            return;
        }

        if(!isset($this->startBreakTick[$name])){
            // InstaBreak?
            $this->addViolation($name);
            $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
        } else {
            $timeDiff = microtime(true) - $this->startBreakTick[$name];
            $expectedDiff = $event->getBlock()->getBreakTime($player->getInventory()->getItemInHand());
            if($timeDiff < $expectedDiff){
                $event->setCancelled();
                if(!isset($this->suspicionLevel[$name])){
                    $this->suspicionLevel[$name] = 0;
                }
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] >= 5){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->suspicionLevel[$name] = 1;
                }
            } else {
                if(isset($this->suspicionLevel[$name])){
                    $this->suspicionLevel[$name] *= 0.5;
                }
            }
        }
    }

}