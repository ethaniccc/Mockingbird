<?php

namespace ethaniccc\Mockingbird\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;

class ClickEvent extends PlayerEvent{

    /** @var float */
    private $previousTime, $newTime;

    /**
     * ClickEvent constructor.
     * @param Player $player
     * @param float $previousTime
     * @param float $newTime
     */
    public function __construct(Player $player, float $previousTime, float $newTime){
        $this->player = $player;
        $this->previousTime = $previousTime;
        $this->newTime = $newTime;
    }

    /**
     * @return float
     */
    public function getCPS() : float{
        return round(1 / ($this->getTimeDiff()), 0);
    }

    /**
     * @return float
     */
    public function getTimeDiff() : float{
        if($this->newTime - $this->previousTime == 0){
            return 0.046;
        }
        return $this->newTime - $this->previousTime;
    }

}