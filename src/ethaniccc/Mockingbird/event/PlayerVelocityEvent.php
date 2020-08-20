<?php

namespace ethaniccc\Mockingbird\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class PlayerVelocityEvent extends PlayerEvent{

    private $velocity;

    public function __construct(Player $player, Vector3 $velocity){
        $this->player = $player;
        $this->velocity = $velocity;
    }

    public function getVelocity() : Vector3{
        return $this->velocity;
    }

}