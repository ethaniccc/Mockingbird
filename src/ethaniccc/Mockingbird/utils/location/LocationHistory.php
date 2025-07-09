<?php

namespace ethaniccc\Mockingbird\utils\location;

use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\EvictingList;
use ethaniccc\Mockingbird\utils\Pair;
use pocketmine\math\Vector3;

class LocationHistory{

	/** @var EvictingList */
	public EvictingList $locations;

	public function __construct(){
		$this->locations = new EvictingList(40);
	}

	/**
	 * @param AABB|Vector3 $pos
	 * @param int          $tick
	 */
	public function addLocation(Vector3|AABB $pos, int $tick) : void{
		$info = new Pair($pos, $tick);
		$this->locations->add($info);
	}

	public function getLocations() : EvictingList{
		return $this->locations;
	}

	/**
	 * @param int $tick - The tick to start searching from
	 * @param int $diff - The amount of ticks to go down.
	 *
	 * @return Vector3[]|AABB[] - An array of Vector3's (this can also be empty in some cases)
	 */
	public function getLocationsRelativeToTime(int $tick, int $diff = 1) : array{
		$locations = [];
		foreach($this->getLocations()->getAll() as $pair){
			/** @var Vector3|AABB $position */
			$position = $pair->getX();
			/** @var int $positionTick */
			$positionTick = $pair->getY();
			if($tick - $positionTick <= $diff){
				$locations[] = $position;
			}
		}
		return $locations;
	}

	public function clearLocations() : void{
		$this->locations->clear();
	}
}