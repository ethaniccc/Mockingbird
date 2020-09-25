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

    private $speeds = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onClick(ClickEvent $event) : void{
        $speed = $event->getTimeDiff();
        // so people who decide just not to click don't stop clicking after a while to bypass this
        // also Horion autoclicker doesn't trigger this event in the air soooooooooo....
        if($speed > 0.5){
            return;
        }
        $player = $event->getPlayer();
        $name = $player->getName();
        if(!isset($this->speeds[$name])){
            $this->speeds[$name] = [];
        }
        if(count($this->speeds[$name]) === $this->getSetting("samples")){
            array_shift($this->speeds[$name]);
        }
        array_push($this->speeds[$name], $speed);
        $deviation = MathUtils::getDeviation($this->speeds[$name]) * 1000;
        // the player's clicking is too consistent and we should add a preVL
        if($deviation <= $this->getSetting("consistency") && !$this->getPlugin()->getUserManager()->get($player)->isMobile() && $event->getCPS() >= $this->getSetting("min_cps")){
            $this->addPreVL($name);
            // if the player still fails after 3 times, we should flag
            if($this->getPreVL($name) >= 3){
                $this->fail($player, $event, $this->formatFailMessage($this->basicFailData($player)), [], "$name: d: $deviation, s: {$this->getSetting("samples")}, cR: {$this->getSetting("consistency")}");
            }
        } else {
            // reward the player
            $this->lowerPreVL($name);
        }
    }

}