<?php

namespace ethaniccc\Mockingbird\cheat\combat\autoclicker;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\ClickEvent;
use ethaniccc\Mockingbird\Mockingbird;

class AutoClickerC extends Cheat{

    private $timeDiffs = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onClick(ClickEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $timeDiff = round($event->getTimeDiff() * 100000, 0);
        if(!isset($this->timeDiffs[$name])){
            $this->timeDiffs[$name] = [];
        }
        if(count($this->timeDiffs[$name]) === 100){
            array_shift($this->timeDiffs[$name]);
        }
        $this->timeDiffs[$name][] = $timeDiff;
        if(count($this->timeDiffs[$name]) === 100){
            $dupedValues = [];
            foreach($this->timeDiffs[$name] as $diff){
                @$dupedValues[$diff]++;
            }
            foreach($dupedValues as $key => $value){
                if($value === 1){
                    unset($dupedValues[$key]);
                }
            }
            $duped = array_sum($dupedValues);
            $this->debugNotify("Click duped: $duped");
            if($duped >= 30){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 10){
                    $this->lowerPreVL($name, 0.8);
                    $this->fail($player, null, "$name had too many duplicated click times", [], "d: $duped, ");
                }
            } else {
                $this->lowerPreVL($name, 0.6);
            }
        }
    }

}