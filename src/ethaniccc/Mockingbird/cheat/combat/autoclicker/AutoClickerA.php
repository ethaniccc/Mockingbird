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

namespace ethaniccc\Mockingbird\cheat\combat\autoclicker;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\ClickEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\MathUtils;

class AutoClickerA extends Cheat{

    /** @var array */
    private $speeds, $deviations = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onClick(ClickEvent $event) : void{
        $speed = $event->getTimeDiff();
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->speeds[$name])){
            $this->speeds[$name] = [];
        }
        if(count($this->speeds[$name]) === 50){
            array_shift($this->speeds[$name]);
        }
        array_push($this->speeds[$name], $speed);
        $deviation = MathUtils::getDeviation($this->speeds[$name]);
        if(!isset($this->deviations[$name])){
            $this->deviations[$name] = [];
        }
        if(count($this->deviations[$name]) === 50){
            array_shift($this->deviations[$name]);
        }
        array_push($this->deviations[$name], $deviation);
        $averageDeviation = MathUtils::getAverage($this->deviations[$name]);
        if($averageDeviation <= 2.5 && !$this->getPlugin()->getUserManager()->get($player)->isMobile() && $event->getCPS() >= 10){
            $this->addPreVL($name);
            if($this->getPreVL($name) >= 4.5){
                $this->fail($player, "$name's clicking was too consistent", [], "$name's click deviation was $averageDeviation");
            }
        } else {
            $this->lowerPreVL($name, 0.9);
        }
    }

}