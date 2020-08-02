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

namespace ethaniccc\Mockingbird\utils;

use pocketmine\block\Block;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;

class LevelUtils{

    public const MODE_X = 0;
    public const MODE_Y = 1;
    public const MODE_Z = 2;
    public const MODE_POINT_DISTANCE = 3;

    public const MODE_NORMAL = 0;
    public const MODE_ID = 1;

    /**
     * @param $player
     * @param float|int $radius
     * @param int $type
     * @return array
     */
    public static function getSurroundingBlocks($player, float $radius = 1, int $type = self::MODE_NORMAL) : array{
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
        if($type === self::MODE_ID){
            $blockIds = [];
            foreach($blocksAroundPlayer as $block){
                array_push($blockIds, $block->getId());
            }
            return $blockIds;
        }
        return $blocksAroundPlayer;
    }

    /**
     * @param Vector3 $to
     * @param Vector3 $from
     * @param int $mode
     * @return float
     */
    public static function getDistance(Vector3 $to, Vector3 $from, int $mode = self::MODE_POINT_DISTANCE) : float{
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

    /**
     * @param Player $player
     * @param float|int $underLevel
     * @return Block
     */
    public static function getBlockUnder(Player $player, float $underLevel = 1) : Block{
        return $player->getLevel()->getBlock($player->asVector3()->subtract(0, $underLevel, 0));
    }

    /**
     * @param Player $player
     * @param float $minY
     * @return bool
     */
    public static function isNearGround(Living $player, float $minY = -1.2) : bool{
        $expand = 0.3;
        $position = $player->asVector3();
        $level = $player->getLevel();
        for($x = -$expand; $x <= $expand; $x += $expand){
            for($z = -$expand; $z <= $expand; $z += $expand){
                for($y = $minY; $y <= -0.5; $y += 0.1){
                    if($level->getBlock($position->add($x, $y, $z))->getId() !== 0){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param Player $player
     * @param int $blockId
     * @param float|int $radius
     * @return bool
     */
    public static function isNearBlock(Player $player, int $blockId, float $radius = 1) : bool{
        for($x = -$radius; $x <= $radius; $x += 0.5){
            for($y = -$radius; $y <= $radius; $y += 0.5){
                for($z = -$radius; $z <= $radius; $z += 0.5){
                    if($player->getLevel()->getBlock($player->asVector3()->add($x, $y, $z))->getId() === $blockId){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function getBlockWalkingOn(Player $player) : ?Block{
        $x = $player->getX();
        $z = $player->getZ();
        for($y = -1; $y <= -0.5; $y += 0.5){
            if($player->getLevel()->getBlockAt($x, $y, $z)->getId() !== 0){
                return $player->getLevel()->getBlockAt($x, $y, $z);
            }
        }
        return null;
    }

}