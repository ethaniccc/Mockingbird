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
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\Air;
use pocketmine\event\player\PlayerJumpEvent;

class AirJump extends Cheat implements StrictRequirements{

    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onJump(PlayerJumpEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!$player->isOnGround()){
            $blocksNear = LevelUtils::getSurroundingBlocks($player, 3);
            $continue = true;
            foreach($blocksNear as $block){
                if(!$block instanceof Air){
                    $continue = false;
                }
            }
            if($continue){
                if(!isset($this->suspicionLevel[$name])){
                    $this->suspicionLevel[$name] = 0;
                }
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] >= 2){
                    $this->addViolation($name);
                    $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
                    $this->suspicionLevel[$name] += 1;
                }
            } else {
                if(isset($this->suspicionLevel[$name])){
                    $this->suspicionLevel[$name] *= 0.75;
                }
            }
        } else {
            if(isset($this->suspicionLevel[$name])){
                $this->suspicionLevel[$name] *= 0.5;
            }
        }
    }
}