<?php

namespace ethaniccc\Mockingbird\utils;

use pocketmine\block\BlockIds;
use pocketmine\math\AxisAlignedBB;

// TODO: Do this bullshit of a mess to prevent falses with unknown blocks
// Whoever PR's this will get free Discord Nitro ($5) please for the love of god....
// Or whoever gives me a better way to get unknown block AABB's *cough* John *cough*
final class UnknownBlockAABBList{

    private static $list = [];

}