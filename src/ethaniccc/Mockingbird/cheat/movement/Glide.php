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
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use ethaniccc\Mockingbird\utils\MathUtils;

class Glide extends Cheat{

    /** @var array */
    private $fallDistances = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();

        if($player->isFlying()){
            return;
        }

        if(!isset($this->fallDistances[$name])){
            $this->fallDistances[$name] = [];
        }

        $yDist = round($event->getDistanceY(), 2);
        if($yDist <= 0){
            unset($this->fallDistances[$name]);
            return;
        }
        $continue = true;
        foreach(LevelUtils::getSurroundingBlocks($player, 3) as $block){
            if($block->getId() !== 0){
                $continue = false;
            }
        }
        if(!$continue){
            return;
        }
        array_push($this->fallDistances[$name], $yDist);
        if(count($this->fallDistances[$name]) >= 15){
            if(MathUtils::getAverage($this->fallDistances[$name]) - $yDist == 0){
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        }
    }

}