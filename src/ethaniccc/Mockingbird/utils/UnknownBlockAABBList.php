<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\BlockFactory;
use ethanicc\Mockingbird\utils\boundingbox\AABB;
// TODO: Do this bullshit of a mess to prevent falses with unknown blocks
// Whoever PR's this will get free Discord Nitro ($5) please for the love of god....
// Or whoever gives me a better way to get unknown block AABB's *cough* John *cough*
final class UnknownBlockAABBList{

    private static $list = [];
    private function __construct(){
        //NOOP
    }
    public static function registerDefaults(): void{
        
    }
    public static function getFromList(Position $pos, int $id, int $meta = 0): AABB{
        $block = BlockFactory::get($id, $meta, new Position(0, 0, 0, $pos->level));
        $default = self::getDefaultAABB();
        $equals = $default->minX == $block->minX and $default->minY == $block->minY and $default->minZ == $block->minZ and $default->maxX == $block->maxX and $default->maxY == $block->maxY and $default->maxZ == $block->maxZ;
        return (self::$list[($id << 4) | $meta] ?? self::$list[$id << 4] ?? ($equals ? $default : $block->getBoundingBox()))->offsetCopy($pos->x, $pos->y, $pos->z);
    }
    private static function getDefaultAABB(): AABB{
        return new AABB(0, 0, 0, 1, 1, 1);
    }
    public static function registerAABB(AABB $aabb, int $id, int $meta = 0): void{
        self::$list[($id << 4) | $meta] = clone $aabb;
    }
}
