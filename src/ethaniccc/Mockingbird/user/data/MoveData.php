<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\block\Block;
use pocketmine\level\Location;
use pocketmine\level\Position;
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
    /** @var bool - Boolean value for if the user is colliding with blocks vertically */
    public $isCollidedVertically = false;
    /** @var Block[] - An array of what blocks the user is colliding with vertically */
    public $verticalCollisions = [];
    /** @var bool - Boolean value for if the user is colliding with blocks horizontally.  */
    public $isCollidedHorizontally = false;
    /** @var Block[] - An array of what blocks the user is colliding with vertically. */
    public $horizontalCollisions = [];
    /** @var Block[] - An array of what ghost blocks the user is colliding with both vertically and horizontally. */
    public $ghostCollisions = [];
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
    /** @var Vector3 - The last direction vector of the user. */
    public $lastDirectionVector;
    /** @var Vector3 - The previous last direction vector of the user. */
    public $previousLastDirectionVector;
    /** @var string[] - The WASD combo the player is using */
    public $pressedKeys = [];
    /** @var int - The amount of client ticks that have passed since colliding with cobweb. */
    public $cobwebTicks = 0;
    /** @var int - The amount of client ticks that have passed since colliding with cobweb */
    public $liquidTicks = 0;
    /** @var int - The amount of client ticks that have passed since colliding with a climbable block. */
    public $climbableTicks = 0;
    /** @var int - The amount of client ticks that have passed since the user was in the (bottom of the) void. */
    public $ticksSinceInVoid = 0;
    /** @var int - The amount of client ticks that have passed since the user has the levitation effect. */
    public $levitationTicks = 0;
    /** @var AABB - The current AABB of the user. */
    public $AABB;
    /** @var bool - The boolean value for wether or not the user is moving in the current tick. */
    public $isMoving = false;
    /** @var Vector3|null - Last position sent to the player by the server. */
    public $forceMoveSync;

}