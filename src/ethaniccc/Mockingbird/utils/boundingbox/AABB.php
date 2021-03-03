<?php

namespace ethaniccc\Mockingbird\utils\boundingbox;

use ethaniccc\Mockingbird\user\User;
use pocketmine\block\Block;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class AABB extends AxisAlignedBB{

    public $minX, $minY, $minZ;
    public $maxX, $maxY, $maxZ;
    public $minVector, $maxVector;

    public const NO_INTERSECTION = -69.0;

    public function __construct(float $minX, $minY, float $minZ, float $maxX, float $maxY, float $maxZ) {
        // TODO: Why is minY sometimes zero? Refer to issue 
        parent::__construct($minX, $minY ?? 0.0, $minZ, $maxX, $maxX, $maxZ);
        $this->minX = $minX;
        $this->minY = $minY;
        $this->minZ = $minZ;
        $this->maxX = $maxX;
        $this->maxY = $maxY;
        $this->maxZ = $maxZ;
        $this->minVector = new Vector3($this->minX, $this->minY, $this->minZ);
        $this->maxVector = new Vector3($this->maxX, $this->maxY, $this->maxZ);
    }

    public static function from(User $user) : AABB{
        $pos = $user->moveData->location;
        return new AABB($pos->x - 0.3, $pos->y, $pos->z - 0.3, $pos->x + 0.3, $pos->y + 1.8, $pos->z + 0.3);
    }

    public static function fromAxisAlignedBB(AxisAlignedBB $alignedBB) : AABB{
        return new AABB($alignedBB->minX - 0.1, $alignedBB->minY, $alignedBB->minZ - 0.1, $alignedBB->maxX + 0.1, $alignedBB->maxY, $alignedBB->maxZ + 0.1);
    }

    public static function fromPosition(Vector3 $pos) : AABB{
        return new AABB($pos->x - 0.3, $pos->y, $pos->z - 0.3, $pos->x + 0.3, $pos->y + 1.8, $pos->z + 0.3);
    }

    public static function fromBlock(Block $block) : AABB{
        $b = $block->getBoundingBox();
        if($b !== null) {
            return new AABB(
                $b->minX, $b->minY, $b->minZ,
                $b->maxX, $b->maxY, $b->maxZ
            );
        } else {
            return new AABB(
                $block->getX(), $block->getY(), $block->getZ(),
                $block->getX() + 1, $block->getY() + 1, $block->getZ() + 1
            );
        }
    }

    public function clone() : AABB{
        return clone $this;
    }

    public function translate(float $x, float $y, float $z) : AABB{
        return new AABB($this->minX + $x, $this->minY + $y, $this->minZ + $z, $this->maxX + $x, $this->maxY, $this->maxZ);
    }

    public function grow(float $x, float $y, float $z) : AABB{
        return new AABB($this->minX - $x, $this->minY - $y, $this->minZ - $z, $this->maxX + $x, $this->maxY, $this->maxZ);
    }

    public function stretch(float $x, float $y, float $z) : AABB{
        return new AABB($this->minX, $this->minY, $this->minZ, $this->maxX + $x, $this->maxY, $this->maxZ);
    }

    public function contains(Vector3 $pos) : bool{
        return $pos->getX() <= $this->maxX
            && $pos->getY() <= $this->maxY
            && $pos->getZ() <= $this->maxZ
            && $pos->getX() >= $this->minX
            && $pos->getY() >= $this->minY
            && $pos->getZ() >= $this->minZ;
    }

    public function min(int $i) : float{
        return [$this->minX, $this->minY, $this->minZ][$i] ?? 0;
    }

    public function max(int $i) : float{
        return [$this->maxX, $this->maxY, $this->maxZ][$i] ?? 0;
    }

    public function getCornerVectors() : array{
        return [
            // top vectors
            new Vector3($this->maxX, $this->maxY, $this->maxZ),
            new Vector3($this->minX, $this->maxY, $this->maxZ),
            new Vector3($this->minX, $this->maxY, $this->minZ),
            new Vector3($this->maxX, $this->maxY, $this->minZ),
            // bottom vectors
            new Vector3($this->maxX, $this->minY, $this->maxZ),
            new Vector3($this->minX, $this->minY, $this->maxZ),
            new Vector3($this->minX, $this->minY, $this->minZ),
            new Vector3($this->maxX, $this->minY, $this->minZ)
        ];
    }

    public function distanceFromVector(Vector3 $vector) : float{
        $distX = max($this->minX - $vector->x, max(0, $vector->x - $this->maxX));
        $distY = max($this->minY - $vector->y, max(0, $vector->y - $this->maxY));
        $distZ = max($this->minZ - $vector->z, max(0, $vector->z - $this->maxZ));
        return sqrt(($distX ** 2) + ($distY ** 2) + ($distZ ** 2));
    }

    public function collidesRay(Ray $ray, float $tmin, float $tmax) : float{
        if($this->isVectorInside($ray->origin)){
            return 0.0;
        } else {
            return ($result = $this->calculateIntercept($ray->traverse($tmin), $ray->traverse($tmax))) !== null ? $ray->origin->distance($result->getHitVector()) : self::NO_INTERSECTION;
        }
        /* for($i = 0; $i < 3; ++$i) {
            $d = 1 / ($ray->direction($i) ?: 0.01);
            $t0 = ($this->min($i) - $ray->origin($i)) * $d;
            $t1 = ($this->max($i) - $ray->origin($i)) * $d;
            if($d < 0) {
                $t = $t0;
                $t0 = $t1;
                $t1 = $t;
            }
            $tmin = $t0 > $tmin ? $t0 : $tmin;
            $tmax = $t1 < $tmax ? $t1 : $tmax;
            if($tmax <= $tmin)
                return -69.0;
        }
        return $tmin; */
    }

}