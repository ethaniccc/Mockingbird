<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\location\LocationHistory;
use pocketmine\math\Vector3;

class TickData{

    /** @var int - The tick the user is currently on. */
    public $currentTick = 0;
    /** @var Vector3[] - The locations of the target entity the user has received. */
    public $targetLocations = [];

}