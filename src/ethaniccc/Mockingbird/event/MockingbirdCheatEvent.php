<?php

namespace ethaniccc\Mockingbird\event;

use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\Cancellable;
use pocketmine\event\player\cheat\PlayerCheatEvent;
use pocketmine\Player;

class MockingbirdCheatEvent extends PlayerCheatEvent implements Cancellable{

    private $cheat;
    private $addedViolations;
    private $extraData;
    private $message;
    private $debugMessage;

    public function __construct(Player $player, Cheat $cheat, string $message, int $addedViolations, array $extraData, ?string $debugMessage){
        $this->player = $player;
        $this->cheat = $cheat;
        $this->message = $message;
        $this->addedViolations = $addedViolations;
        $this->extraData = $extraData;
        $this->debugMessage = $debugMessage;
    }

    public function getCheat() : Cheat{
        return $this->cheat;
    }

    public function getAddedViolations() : int{
        return $this->addedViolations;
    }

    public function setAddedViolations(int $addedViolations) : void{
        $this->addedViolations = $addedViolations;
    }

    public function getExtraData() : array{
        return $this->extraData;
    }

    public function getMessage() : string{
        return $this->message;
    }

    public function getDebugMessage() : string{
        return $this->debugMessage;
    }

}