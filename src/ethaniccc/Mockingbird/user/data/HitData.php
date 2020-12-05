<?php

namespace ethaniccc\Mockingbird\user\data;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class HitData{

    /** @var null|Entity - The entity the user attacked.  */
    public $targetEntity;
    /** @var Vector3 - The position the client was at when attacking. */
    public $attackPos;
    /** @var bool - The boolean value if the user's hit is in attack cooldown. */
    public $inCooldown = false;
    /** @var int - The last tick since the user was in cooldown. */
    public $lastTick = 0;

}