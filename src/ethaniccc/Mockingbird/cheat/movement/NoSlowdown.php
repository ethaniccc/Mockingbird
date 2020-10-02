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
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\item\Bow;
use pocketmine\item\Consumable;
use pocketmine\item\Food;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class NoSlowdown extends Cheat implements StrictRequirements{

    private $usingItemTicks = [];
    private $useIsValid;

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
        $this->useIsValid = function(Player $player) : bool{
            if(($item = $player->getInventory()->getItemInHand()) instanceof Consumable){
                if($player->getFood() < $player->getMaxFood()){
                    return true;
                } else {
                    if($item instanceof Food){
                        return in_array($item->getId(), [ItemIds::GOLDEN_APPLE, ItemIds::ENCHANTED_GOLDEN_APPLE]);
                    } else {
                        return true;
                    }
                }
            } else {
                return $item instanceof Bow;
            }
        };
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $player->getName();
        if($player->isFlying()){
            return;
        }
        // why is this true after right clicking in air with item in hand??
        if($player->isUsingItem()){
            // why... :sob:
            if(!($this->useIsValid)($player)){
                return;
            }
            if(!isset($this->usingItemTicks[$name])){
                $this->usingItemTicks[$name] = 0;
            }
            ++$this->usingItemTicks[$name];
            if($this->usingItemTicks[$name] < 9){
                return;
            }
            $currentMoveDist = $user->getMoveDistance();
            $lastMoveDist = $user->getLastMoveDistance();
            $expectedMoveDist = $lastMoveDist * 0.2;
            $equalness = $currentMoveDist - $expectedMoveDist;
            $effectLevel = $player->getEffect(1) === null ? 0 : $player->getEffect(1)->getAmplifier() + 1;
            if($equalness > $this->getSetting("max_breach") && $effectLevel <= 5
            && $user->timePassedSinceMotion(20)){
                $this->addPreVL($name);
                $maxPreVL = (int) ($player->getPing() / 50) + 4;
                if($this->getPreVL($name) >= $maxPreVL){
                    $this->suppress($event);
                    $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "$name breached prediction with a prediction difference of $equalness");
                }
            } else {
                $this->lowerPreVL($name, 0);
            }
        } else {
            unset($this->usingItemTicks[$name]);
        }
    }

    public function onConsume(PlayerItemConsumeEvent $event) : void{
        // WHYYY?!?!?!?!
        unset($this->usingItemTicks[$event->getPlayer()->getName()]);
    }

}