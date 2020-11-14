<?php

namespace ethaniccc\Mockingbird\utils\boundingbox;

use ethaniccc\Mockingbird\user\User;
use pocketmine\block\Block;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class AABB extends AxisAlignedBB{

    public $minX, $minY, $minZ;
    public $maxX, $maxY, $maxZ;

    public function __construct(float $minX, float $minY, float $minZ, float $maxX, float $maxY, float $maxZ) {
        parent::__construct($minX, $minY, $minZ, $maxX, $maxX, $maxZ);
        $this->minX = $minX;
        $this->minY = $minY;
        $this->minZ = $minZ;
        $this->maxX = $maxX;
        $this->maxY = $maxY;
        $this->maxZ = $maxZ;
    }

    public static function from(User $user) : AABB{
        $pos = $user->moveData->location;
        return new AABB($pos->x - 0.3, $pos->y, $pos->z - 0.3, $pos->x + 0.3, $pos->y + 1.8, $pos->z + 0.3);
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
            // apparently some blocks (Cobweb in my case) have no AABB
            return new AABB(
                $block->getX(), $block->getY(), $block->getZ(),
                $block->getX() + 1, $block->getY() + 1, $block->getZ() + 1
            );
        }
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

    public function collidesRay(Ray $ray, float $maxDist) : float{
        $pos1 = $ray->getOrigin();
        $pos2 = $pos1->add($ray->getDirection()->multiply($maxDist));
        $v1 = $pos1->getIntermediateWithXValue($pos2, $this->minX);
        $v2 = $pos1->getIntermediateWithXValue($pos2, $this->maxX);
        $v3 = $pos1->getIntermediateWithYValue($pos2, $this->minY);
        $v4 = $pos1->getIntermediateWithYValue($pos2, $this->maxY);
        $v5 = $pos1->getIntermediateWithZValue($pos2, $this->minZ);
        $v6 = $pos1->getIntermediateWithZValue($pos2, $this->maxZ);
        if($v1 !== null and !$this->isVectorInYZ($v1)){
            $v1 = null;
        }
        if($v2 !== null and !$this->isVectorInYZ($v2)){
            $v2 = null;
        }
        if($v3 !== null and !$this->isVectorInXZ($v3)){
            $v3 = null;
        }
        if($v4 !== null and !$this->isVectorInXZ($v4)){
            $v4 = null;
        }
        if($v5 !== null and !$this->isVectorInXY($v5)){
            $v5 = null;
        }
        if($v6 !== null and !$this->isVectorInXY($v6)){
            $v6 = null;
        }
        $vector = null;
        $distance = PHP_INT_MAX;
        foreach([$v1, $v2, $v3, $v4, $v5, $v6] as $v){
            if($v !== null and ($d = $pos1->distanceSquared($v)) < $distance){
                $vector = $v;
                $distance = $d;
            }
        }

        return $vector !== null ? sqrt($distance) : -69.0;
    }

}