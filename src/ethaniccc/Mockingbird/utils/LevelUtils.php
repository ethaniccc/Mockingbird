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

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\user\User;
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
     * @param float $underLevel
     * @return Block
     */
    public static function getBlockUnder(Player $player, float $underLevel = 1) : Block{
        return $player->getLevel()->getBlock($player->asVector3()->subtract(0, $underLevel, 0));
    }

    /**
     * @param User $user
     * @param float $minY
     * @return bool
     */
    public static function isNearGround(User $user, float $minY = -0.5) : bool{
        // thank you @very nice name#6789
        $position = $user->getCurrentLocation();
        if($position !== null){
            $AABB = AABB::fromPosition($position);
            $AABB->minY -= 0.05;
            $AABB->expand(0.15, 0, 0.15);
            if(count($user->getPlayer()->getLevel()->getCollisionBlocks($AABB, true)) > 0){
                return true;
            } else {
                $expand = 0.3;
                for($x = -$expand; $x <= $expand; $x += $expand){
                    for($z = -$expand; $z <= $expand; $z += $expand){
                        $block = $user->getPlayer()->getLevel()->getBlockAt($position->x + $x, $position->y - 0.1, $position->z + $z);
                        if($block->getId() !== 0){
                            return true;
                        }
                    }
                }
                return false;
            }
        }
        return false;
    }

    /**
     * @param User $user
     * @param int $blockId
     * @param float $radius
     * @return bool
     */
    public static function isNearBlock(User $user, int $blockId, float $radius = 1) : bool{
        for($x = -$radius; $x <= $radius; $x += 0.5){
            for($y = -$radius; $y <= $radius; $y += 0.5){
                for($z = -$radius; $z <= $radius; $z += 0.5){
                    if($user->getPlayer()->getLevel()->getBlock(($user->getCurrentLocation() !== null ? $user->getCurrentLocation() : $user->getPlayer()->asVector3())->add($x, $y, $z))->getId() === $blockId){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static function getBlockAbove(Player $player) : Block{
        return $player->getLevel()->getBlock($player->add(0, 2, 0));
    }

}