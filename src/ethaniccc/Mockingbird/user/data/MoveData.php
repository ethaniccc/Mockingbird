<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\block\Block;
use pocketmine\level\Location;
use pocketmine\math\Vector3;

class MoveData{

    /** @var Location - The current and previous location of the user. */
    public $location, $lastLocation;
    /** @var Vector3 - The move delta's of the user. */
    public $moveDelta, $lastMoveDelta;
    /** @var bool - If the player is currently on the ground (server-side). */
    public $onGround = false;
    /** @var int - The amount of client ticks the user has been off and on the ground. */
    public $onGroundTicks = 0, $offGroundTicks = 0;
    /** @var Location - The last onGround location of the User */
    public $lastOnGroundLocation;
    /** @var null|Block - THe blocks above and below the user from the move location.*/
    public $blockAbove, $blockBelow;
    /** @var Vector3 - The last motion the user has took. */
    public $lastMotion;
    /** @var float - The current and previous yaw and pitch. */
    public $yaw = 0, $pitch = 0, $lastYaw = 0, $lastPitch = 0;
    /** @var float - The differences of the yaw and pitch. */
    public $yawDelta = 0, $pitchDelta = 0, $lastYawDelta = 0, $lastPitchDelta = 0;
    /** @var bool - The boolean value of if the user has rotated. */
    public $rotated = false;
    /** @var Vector3 - The current direction vector of the user. */
    public $directionVector;
    /** @var string[] - The WASD combo the player is using */
    public $pressedKeys = [];
    /** @var int - The amount of client ticks that have passed since colliding with cobweb. */
    public $cobwebTicks = 0;
    /** @var int - The amount of client ticks that have passed since colliding with cobweb */
    public $liquidTicks = 0;
    /** @var AABB - The current AABB of the user. */
    public $AABB;
}