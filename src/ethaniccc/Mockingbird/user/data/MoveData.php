<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;

class MoveData{

	public Location $location, $lastLocation;
	public Vector3 $moveDelta, $lastMoveDelta;
	public bool $onGround = false;
	public int $onGroundTicks = 0, $offGroundTicks = 0;
	public Location $lastOnGroundLocation;
	public bool $isCollidedVertically = false;
	public array $verticalCollisions = [];
	public bool $isCollidedHorizontally = false;
	public array $horizontalCollisions = [];
	public array $ghostCollisions = [];
	public Vector3 $lastMotion;
	public float $yaw = 0, $pitch = 0, $lastYaw = 0, $lastPitch = 0;
	public float $yawDelta = 0, $pitchDelta = 0, $lastYawDelta = 0, $lastPitchDelta = 0;
	public bool $rotated = false;
	public Vector3 $directionVector;
	public Vector3 $lastDirectionVector;
	public array $pressedKeys = [];
	public int $cobwebTicks = 0;
	public int $liquidTicks = 0;
	public int $climbableTicks = 0;
	public int $ticksSinceInVoid = 0;
	public int $levitationTicks = 0;
	public AABB $AABB;
	public bool $isMoving = false;
}