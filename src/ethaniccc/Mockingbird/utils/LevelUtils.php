<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\Player;

class LevelUtils{

    public static function getSurroundingBlocks(Player $player, float $radius = 1) : ?array{
        $position = $player->asVector3();
        $level = $player->getLevel();
        $addVar = 0;
        if($radius > 1){
            $addVar = $radius - 2;
        }
        $currentAddLevel = 0;
        $levelsCompleted = 0;
        $blocksAroundPlayer = [
        ];
        while($levelsCompleted != $radius){
            array_push($blocksAroundPlayer, $level->getBlock($position->add(0 + $currentAddLevel, 0 + $currentAddLevel, 0 + $currentAddLevel)));
            if($currentAddLevel != 0){
                array_push($blocksAroundPlayer, $level->getBlock($position->add(0 - $currentAddLevel, 0 - $currentAddLevel, 0 - $currentAddLevel)));
            }
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 + $currentAddLevel, 0 + $currentAddLevel, 0 + $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 - $currentAddLevel, 0 - $currentAddLevel, 0 - $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(0 + $currentAddLevel, 0 + $currentAddLevel, 1 + $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(0 - $currentAddLevel, 0 - $currentAddLevel, 1 - $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 + $currentAddLevel, 0 + $currentAddLevel, 1 + $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 + $currentAddLevel, 0 - $currentAddLevel, 1 + $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 - $currentAddLevel, 0, 1 + $currentAddLevel)));
            array_push($blocksAroundPlayer, $level->getBlock($position->add(1 + $currentAddLevel, 0, 1 - $currentAddLevel)));
            $currentAddLevel++;
            $levelsCompleted++;
        }
        return $blocksAroundPlayer;
    }

}