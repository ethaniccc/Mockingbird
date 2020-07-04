<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class LevelUtils{

    public const MODE_X = 0;
    public const MODE_Y = 1;
    public const MODE_Z = 2;
    public const MODE_POINT_DISTANCE = 3;

    public static function getSurroundingBlocks(Player $player, float $radius = 1) : array{
        $position = $player->asVector3();
        $level = $player->getLevel();
        $currentAddLevel = 0;
        $levelsCompleted = 0;
        $blocksAroundPlayer = [];
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

    public static function getMoveDistance(Vector3 $to, Vector3 $from, int $mode) : float{
        switch($mode){
            case self::MODE_X:
                return abs($to->getX() - $from->getX());
            case self::MODE_Y:
                return abs($to->getY() - $from->getY());
            case self::MODE_Z:
                return abs($to->getZ() - $from->getZ());
            case self::MODE_POINT_DISTANCE:
                $distX = $to->getX() - $from->getX();
                $distZ = $to->getZ() - $from->getZ();
                $distanceSquared = $distX * $distX + $distZ * $distZ;
                return abs(sqrt($distanceSquared));
            default:
                Server::getInstance()->getLogger()->debug("Unknown mode given: $mode");
                return 0.0;
        }
    }

}