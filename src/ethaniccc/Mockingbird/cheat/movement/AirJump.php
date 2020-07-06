<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\Air;
use pocketmine\event\player\PlayerJumpEvent;
use ethaniccc\Mockingbird\cheat\StrictRequirments;

class AirJump extends Cheat implements StrictRequirments{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onJump(PlayerJumpEvent $event) : void{
        // This was a simple check, but I still haven't taken into consider lag.
        // According to an issue, onGround may give inaccurate results:
        // https://github.com/pmmp/PocketMine-MP/issues/3598
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
                $this->addViolation($name);
                $this->notifyStaff($name, $this->getName(), $this->genericAlertData($player));
            }
        }
    }
}