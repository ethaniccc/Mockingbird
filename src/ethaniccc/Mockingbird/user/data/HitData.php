<?php

namespace ethaniccc\Mockingbird\user\data;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class HitData{

	public ?Entity $targetEntity;
	public ?Entity $lastTargetEntity;
	public Vector3 $attackPos;
	public bool $inCooldown = false;
	public int $lastTick = 0;
}