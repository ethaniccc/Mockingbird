<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\block\{BlockFactory, BlockIds};
use ethanicc\Mockingbird\utils\boundingbox\AABB;

final class UnknownBlockAABBList{

    private static $list = [];
    private function __construct(){
        //NOOP
    }
    public static function registerDefaults(): void{
        self::registerAABB(new AABB(0.125, 0.0, 0.125, 0.875, 0.875, 0.875), BlockIds::BREWING_STAND);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.75, 1.0), BlockIds::ENCHANTING_TABLE);
        //Lever
        self::registerAABB(new AABB(0.25, 0.375, 0.25, 0.75, 1.0, 0.75), BlockIds::LEVER);
        self::registerAABB(new AABB(0.3125, 0.25, 0.625, 0.625, 0.75, 1.0), BlockIds::LEVER, 1);
        self::registerAABB(new AABB(0.3125, 0.25, 0.0, 0.625, 0.75, 0.375), BlockIds::LEVER, 2);
        self::registerAABB(new AABB(0.0, 0.25, 0.3125, 0.375, 0.75, 0.625), BlockIds::LEVER, 3);
        self::registerAABB(new AABB(0.625, 0.25, 0.3125, 1.0, 0.75, 0.625), BlockIds::LEVER, 4);
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 0.625, 0.75), BlockIds::LEVER, 5);
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 0.625, 0.75), BlockIds::LEVER, 6);
        self::registerAABB(new AABB(0.25, 0.375, 0.25, 0.75, 1.0, 0.75), BlockIds::LEVER, 7);
        self::registerAABB(new AABB(0.25, 0.375, 0.25, 0.75, 1.0, 0.75), BlockIds::LEVER, 8);
        self::registerAABB(new AABB(0.3125, 0.25, 0.625, 0.625, 0.75, 1.0), BlockIds::LEVER, 9);
        self::registerAABB(new AABB(0.3125, 0.25, 0.0, 0.625, 0.75, 0.375), BlockIds::LEVER, 10);
        self::registerAABB(new AABB(0.0, 0.25, 0.3125, 0.375, 0.75, 0.625), BlockIds::LEVER, 11);
        self::registerAABB(new AABB(0.625, 0.25, 0.3125, 1.0, 0.75, 0.625), BlockIds::LEVER, 12);
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 0.625, 0.75), BlockIds::LEVER, 13);
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 0.625, 0.75), BlockIds::LEVER, 14);
        self::registerAABB(new AABB(0.25, 0.375, 0.25, 0.75, 1.0, 0.75), BlockIds::LEVER, 15);
        //Pressure Plates
        foreach([BlockIds::STONE_PRESSURE_PLATE, BlockIds::WOODEN_PRESSURE_PLATE, BlockIds::LIGHT_WEIGHTED_PRESSURE_PLATE, BlockIds::HEAVY_WEIGHTED_PRESSURE_PLATE] as $id){
            self::registerAABB(new AABB(0.0625, 0.0, 0.0625, 0.9375, 0.0625, 0.9375), $id);
            foreach(range(1, 15) as $meta){
                self::registerAABB(new AABB(0.0625, 0.0, 0.03125, 0.9375, 0.0625, 0.9375), $id, $meta);
            }
        }
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 1.0, 0.75), BlockIds::STANDING_SIGN);
        self::registerAABB(new AABB(0.875, 0.28125, 0.0, 1.0, 0.78125, 1.0), BlockIds::WALL_SIGN, 2);
        self::registerAABB(new AABB(0.0, 0.28125, 0.0, 0.125, 0.78125, 1.0), BlockIds::WALL_SIGN, 3);
        self::registerAABB(new AABB(0.0, 0.28125, 0.0, 1.0, 0.78125, 0.125), BlockIds::WALL_SIGN, 4);
        self::registerAABB(new AABB(0.0, 0.28125, 0.875, 1.0, 0.78125, 1.0), BlockIds::WALL_SIGN, 5);
    }
    public static function getFromList(Vector3 $pos, int $id, int $meta = 0): AABB{
        return (self::$list[($id << 4) | $meta] ?? self::$list[$id << 4] ?? AABB::fromBlock(BlockFactory::get($id, $meta)->setComponents(0, 0, 0)))->offsetCopy($pos->x, $pos->y, $pos->z);
    }
    public static function registerAABB(AABB $aabb, int $id, int $meta = 0): void{
        self::$list[($id << 4) | $meta] = clone $aabb;
    }
}
