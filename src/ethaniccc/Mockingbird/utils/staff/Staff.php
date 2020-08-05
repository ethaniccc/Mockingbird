<?php

namespace ethaniccc\Mockingbird\utils\staff;

use pocketmine\Player;
use pocketmine\Server;

class Staff{

    /** @var string */
    private $player = "";
    private $alertsEnabled = true;
    private $debugMessages = false;

    public function __construct(string $name){
        $this->player = $name;
    }

    public function getPlayer() : ?Player{
        return Server::getInstance()->getPlayer($this->player);
    }

    public function hasAlertsEnabled() : bool{
        return $this->alertsEnabled;
    }

    public function setAlertsEnabled(bool $alertsEnabled) : void{
        $this->alertsEnabled = $alertsEnabled;
    }

    public function hasDebugMessagesEnabled() : bool{
        return $this->debugMessages;
    }

    public function setDebugMessagesEnabled(bool $enabled = true) : void{
        $this->debugMessages = $enabled;
    }

}