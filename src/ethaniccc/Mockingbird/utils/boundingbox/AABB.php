<?php

/*
$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\utils\boundingbox;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class AABB
 * @package ethaniccc\Mockingbird\utils\boundingbox
 * @author shura62 (tysm <3)
 */
class AABB{

    private $minX, $minY, $minZ;
    private $maxX, $maxY, $maxZ;

    public function __construct(float $minX, float $minY, float $minZ, float $maxX, float $maxY, float $maxZ) {
        $this->minX = $minX;
        $this->minY = $minY;
        $this->minZ = $minZ;
        $this->maxX = $maxX;
        $this->maxY = $maxY;
        $this->maxZ = $maxZ;
    }

    public static function from(Entity $user) : AABB{
        $pos = $user->getPosition();
        return new AABB($pos->x - 0.3, $pos->y, $pos->z - 0.3, $pos->x + 0.3, $pos->y + 1.8, $pos->z + 0.3);
    }

    public static function fromPosition(Vector3 $pos) : AABB{
        return new AABB($pos->x - 0.3, $pos->y, $pos->z - 0.3, $pos->x + 0.3, $pos->y + 1.8, $pos->z + 0.3);
    }

    public static function fromBlock(Block $block) : ?AABB{
        $b = $block->getBoundingBox();
        if($b !== null) {
            return new AABB(
                $b->minX, $b->minY, $b->minZ,
                $b->maxX, $b->maxY, $b->maxZ
            );
        }
        return null;
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

    public function collidesRay(Ray $ray, float $tmin, float $tmax) : float{
        for($i = 0; $i < 3; ++$i) {
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
            if($tmax <= $tmin){
                return -1;
            }
        }
        return $tmin;
    }

    public function getMin() : Vector3{
        return new Vector3($this->minX, $this->minY, $this->minZ);
    }

    public function getMax() : Vector3{
        return new Vector3($this->maxX, $this->maxY, $this->maxZ);
    }

}