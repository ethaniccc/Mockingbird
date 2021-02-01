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
            for($i = 1; $i <= 15; ++$i){
                self::registerAABB(new AABB(0.0625, 0.0, 0.0625, 0.9375, 0.03125, 0.9375), $id, $i);
            }
        }
        //Signs
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 1.0, 0.75), BlockIds::STANDING_SIGN);
        self::registerAABB(new AABB(0.875, 0.28125, 0.0, 1.0, 0.78125, 1.0), BlockIds::WALL_SIGN, 2);
        self::registerAABB(new AABB(0.0, 0.28125, 0.0, 0.125, 0.78125, 1.0), BlockIds::WALL_SIGN, 3);
        self::registerAABB(new AABB(0.0, 0.28125, 0.0, 1.0, 0.78125, 0.125), BlockIds::WALL_SIGN, 4);
        self::registerAABB(new AABB(0.0, 0.28125, 0.875, 1.0, 0.78125, 1.0), BlockIds::WALL_SIGN, 5);
        //Daylight Sensor
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.375, 1.0), BlockIds::DAYLIGHT_SENSOR);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.375, 1.0), BlockIds::DAYLIGHT_SENSOR_INVERTED);
        //Wheat
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.140125, 1.0), BlockIds::WHEAT_BLOCK);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.28125, 1.0), BlockIds::WHEAT_BLOCK, 1);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.4375, 1.0), BlockIds::WHEAT_BLOCK, 2);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.5625, 1.0), BlockIds::WHEAT_BLOCK, 3);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.71875, 1.0), BlockIds::WHEAT_BLOCK, 4);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.90625, 1.0), BlockIds::WHEAT_BLOCK, 5);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 1.0, 1.0), BlockIds::WHEAT_BLOCK, 6);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 1.140125, 1.0), BlockIds::WHEAT_BLOCK, 7);
        //Mushrooms
        self::registerAABB(new AABB(0.3125, 0.0, 0.3125, 0.6875, 0.375, 0.6875), BlockIds::RED_MUSHROOM);
        self::registerAABB(new AABB(0.3125, 0.0, 0.3125, 0.6875, 0.375, 0.6875), BlockIds::BROWN_MUSHROOM);
        //Nether Wart
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.25, 1.0), BlockIds::NETHER_WART);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.5, 1.0), BlockIds::NETHER_WART, 1);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.75, 1.0), BlockIds::NETHER_WART, 2);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 1.0, 1.0), BlockIds::NETHER_WART, 3);
        //Stems
        foreach([BlockIds::PUMPKIN_STEM, BlockIds::MELON_STEM] as $stem){
            self::registerAABB(new AABB(0.375, 0.0, 0.375, 0.625, 0.125, 0.625), $id);
            for($i = 2; $i <= 8; ++$i){
                self::registerAABB(new AABB(0.375, 0.0, 0.375, 0.625, $i*0.125, 0.625), $id, $i-1);
            }
        }
        //Carrots
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.09375, 1.0), BlockIds::CARROTS);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.1875, 1.0), BlockIds::CARROTS, 1);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.3125, 1.0), BlockIds::CARROTS, 2);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.40625, 1.0), BlockIds::CARROTS, 3);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.5, 1.0), BlockIds::CARROTS, 4);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.59375, 1.0), BlockIds::CARROTS, 5);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.6875, 1.0), BlockIds::CARROTS, 6);
        self::registerAABB(new AABB(0.0  0.0, 0.0, 1.0, 0.8125, 1.0), BlockIds::CARROTS, 7);
        //Beetroot/Potatoes
        foreach([BlockIds::BEETROOT, BlockIds::POTATOES] as $crop){
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.09375, 1.0), $crop);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.1875, 1.0), $crop, 1);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.25, 1.0), $crop, 2);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.34375, 1.0), $crop, 3);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.4375, 1.0), $crop, 4);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.5, 1.0), $crop, 5);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.59375, 1.0), $crop, 6);
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.6975, 1.0), $crop, 7);
        }
        //Tripwire
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.5, 1.0), BlockIds::TRIPWIRE);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.09375, 1.0), BlockIds::TRIPWIRE, 1);
        //Sapling
        self::registerAABB(new AABB(0.09375, 0.0, 0.09375, 0.90625, 0.8125, 0.90625), BlockIds::SAPLING);
        //Banner
        self::registerAABB(new AABB(0.25, 0.0, 0.25, 0.75, 1.0, 0.75), BlockIds::STANDING_BANNER);
        self::registerAABB(new AABB(0.0, 0.0, 0.875, 1.0, 0.78125, 1.0), BlockIds::WALL_BANNER, 2);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.78125, 0.125), BlockIds::WALL_BANNER, 3);
        self::registerAABB(new AABB(0.875, 0.0, 0.0, 1.0, 0.78125, 1.0), BlockIds::WALL_BANNER, 4);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 0.125, 0.78125, 1.0), BlockIds::WALL_BANNER, 5);
        //Dead bush
        self::registerAABB(new AABB(0.3125, 0.0, 0.3125, 0.6875, 0.59375, 0.6875), BlockIds::DEAD_BUSH);
        //Vine
        for($i = 3; $i <= 15; ++$i){
            self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 1.0, 1.0), BlockIds::VINE, $i);
        }
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 0.0625, 1.0), BlockIds::VINE);
        self::registerAABB(new AABB(0.0, 0.0, 0.9375, 1.0, 1.0, 1.0), BlockIds::VINE, 1);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 0.0625, 1.0, 1.0), BlockIds::VINE, 2);
        self::registerAABB(new AABB(0.0, 0.0, 0.0, 1.0, 1.0, 0.0625), BlockIds::VINE, 4);
        self::registerAABB(new AABB(0.9375, 0.0, 0.0, 1.0, 1.0, 1.0), BlockIds::VINE, 8);
        //Torches
        foreach([BlockIds::UNLIT_REDSTONE_TORCH, BlockIds::REDSTONE_TORCH, BlockIds::TORCH] as $torch){
            self::registerAABB(new AABB(0.0, 0.203125, 0.34375, 0.3125, 0.796875, 0.65625), $torch, 1);
            self::registerAABB(new AABB(0.6875, 0.203125, 0.34375, 1.0, 0.796875, 0.65625), $torch, 2);
            self::registerAABB(new AABB(0.34375, 0.203125, 0.0, 0.65625, 0.796875, 0.3125), $torch, 3);
            self::registerAABB(new AABB(0.34375, 0.203125, 0.6875, 0.65625, 0.796875, 1.0), $torch, 4);
            self::registerAABB(new AABB(0.40625, 0.0, 0.40625, 0.59375, 0.59375, 0.59375), $torch, 5);
        }
        //Buttons
        foreach([BlockIds::STONE_BUTTON, BlockIds::WOODEN_BUTTON] as $button){
            self::registerAABB(new AABB(0.3125, 0.875, 0.375, 0.6875, 1.0, 0.625), $button);
            self::registerAABB(new AABB(0.3125, 0.0, 0.375, 0.6875, 0.125, 0.625), $button, 1);
            self::registerAABB(new AABB(0.3125, 0.375, 0.875, 0.6875, 0.625, 1.0), $button, 2);
            self::registerAABB(new AABB(0.3125, 0.375, 0.0, 0.6875, 0.625, 0.125), $button, 3);
            self::registerAABB(new AABB(0.875, 0.375, 0.3125, 1.0, 0.625, 0.6875), $button, 4);
            self::registerAABB(new AABB(0.0, 0.375, 0.3125, 0.125, 0.625, 0.6875), $button, 5);
            self::registerAABB(new AABB(0.3125, 0.9375, 0.375, 0.6875, 1.0, 0.625), $button, 6);
            self::registerAABB(new AABB(0.3125, 0.9375, 0.375, 0.6875, 1.0, 0.625), $button, 7);
            self::registerAABB(new AABB(0.3125, 0.375, 0.9375, 0.6875, 0.625, 1.0), $button, 8);
            self::registerAABB(new AABB(0.3125, 0.375, 0.0, 0.6875, 0.625, 0.0625), $button, 9);
            self::registerAABB(new AABB(0.9375, 0.375, 0.3125, 1.0, 0.625, 0.6875), $button, 10);
            self::registerAABB(new AABB(0.0, 0.375, 0.3125, 0.0625, 0.625, 0.6875), $button, 11);
            self::registerAABB(new AABB(0.9375, 0.375, 0.3125, 1.0, 0.625, 0.6875), $button, 12);
            self::registerAABB(new AABB(0.0, 0.375, 0.3125, 0.0625, 0.625, 0.6875), $button, 13);
            self::registerAABB(new AABB(0.3125, 0.9375, 0.375, 0.6875, 1.0, 0.625), $button, 14);
            self::registerAABB(new AABB(0.3125, 0.9375, 0.375, 0.6875, 1.0, 0.625), $button, 15);
        }
        //Fence Gates
        foreach([BlockIds::FENCE_GATE, BlockIds::SPRUCE_FENCE_GATE, BlockIds::BIRCH_FENCE_GATE, BlockIds::JUNGLE_FENCE_GATE, BlockIds::DARK_OAK_FENCE_GATE, BlockIds::ACACIA_FENCE_GATE] as $gate){
            for($i = 0; $i <= 15; ++$i){
                if($i%2 == 0){
                    self::registerAABB(new AABB(0.0, 0.0, 0.375, 1.0, 1.0, 0.625), $gate, $i);
                }else{
                    self::registerAABB(new AABB(0.375, 0.0, 0.0, 0.625, 1.0, 1.0), $gate, $i);
                }
            }
        }
        //Tripwire Hook
        for($i = 0; $i <= 15; ++$i){
            switch($i%4){
                case 0:
                    self::registerAABB(new AABB(0.375, 0.0625, 0.0, 0.625, 0.5625, 0.375), BlockIds::TRIPWIRE_HOOK, $i);
                    break;
                case 1:
                    self::registerAABB(new AABB(0.625, 0.0625, 0.375, 1.0, 0.5625, 0.625), BlockIds::TRIPWIRE_HOOK, $i);
                    break;
                case 2:
                    self::registerAABB(new AABB(0.375, 0.0625, 0.625, 0.625, 0.5625, 1.0), BlockIds::TRIPWIRE_HOOK, $i);
                    break;
                case 3:
                    self::registerAABB(new AABB(0.0, 0.0625, 0.375, 0.375, 0.5625, 0.625), BlockIds::TRIPWIRE_HOOK, $i);
                    break;
            }
        }
    }
    public static function getFromList(Vector3 $pos, int $id, int $meta = 0): AABB{
        return (self::$list[($id << 4) | $meta] ?? self::$list[$id << 4] ?? AABB::fromBlock(BlockFactory::get($id, $meta)->setComponents(0, 0, 0)))->offsetCopy($pos->x, $pos->y, $pos->z);
    }
    public static function registerAABB(AABB $aabb, int $id, int $meta = 0): void{
        self::$list[($id << 4) | $meta] = clone $aabb;
    }
}
