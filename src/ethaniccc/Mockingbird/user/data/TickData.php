<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\location\LocationHistory;

class TickData{

    /** @var int - The tick the user is currently on. */
    public $currentTick = 0;
    /** @var LocationHistory - The location history of the target entity. */
    public $targetLocationHistory;

}