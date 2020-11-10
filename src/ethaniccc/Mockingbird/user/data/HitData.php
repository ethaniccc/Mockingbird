<?php

namespace ethaniccc\Mockingbird\user\data;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class HitData{

    /** @var Entity - The entity the user attacked.  */
    public $targetEntity;
    /** @var Vector3 - The position the client was at when attacking. */
    public $attackPos;
    /** @var float - The ray distance of the user to the target entity. */
    public $rayDistance;
    /** @var bool - The boolean value if the user's ray collides with the entities hitbox. */
    public $rayCollides = true;
    /** @var bool - The boolean value if the user's hit is in attack cooldown. */
    public $inCooldown = false;

}