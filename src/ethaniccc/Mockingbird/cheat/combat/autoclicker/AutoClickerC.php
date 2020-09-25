<?php

namespace ethaniccc\Mockingbird\cheat\combat\autoclicker;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\ClickEvent;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\utils\TextFormat;

class AutoClickerC extends Cheat{

    private $timeDiffs = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    // this falses if double clicking with butterfly consistently enough
    public function onClick(ClickEvent $event) : void{
        $player = $event->getPlayer();
        $player->sendActionBarMessage(TextFormat::YELLOW . "CPS: " . TextFormat::LIGHT_PURPLE . $event->getCPS());
        $name = $player->getName();
        $timeDiff = round($event->getTimeDiff() * 10000000, 0);
        if($event->getTimeDiff() > 0.125){
            return;
        }
        if(!isset($this->timeDiffs[$name])){
            $this->timeDiffs[$name] = [];
        }
        if(count($this->timeDiffs[$name]) === 125){
            array_shift($this->timeDiffs[$name]);
        }
        $this->timeDiffs[$name][] = $timeDiff;
        if(count($this->timeDiffs[$name]) === 125){
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
            if($duped >= 4){
                $this->addPreVL($name);
                if($this->getPreVL($name) >= 10){
                    $this->fail($player, null, "$name had too many duplicated click times", [], "d: $duped");
                    $this->timeDiffs[$name] = [];
                }
            } else {
                $this->lowerPreVL($name, 0.6);
            }
        }
    }

}