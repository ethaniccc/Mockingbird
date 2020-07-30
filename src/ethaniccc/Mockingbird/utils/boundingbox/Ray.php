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

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\Player;

/**
 * Class Ray
 * @package ethaniccc\Mockingbird\utils\boundingbox
 * @author shura62 (tysm)
 */
class Ray{

    /** @var Vector3 */
    private $origin, $direction;

    public static function from(Entity $player) : Ray{
        return new Ray($player->add(0, $player->getEyeHeight(), 0), $player->getDirectionVector());
    }

    public function __construct(Vector3 $origin, Vector3 $direction){
        $this->origin = $origin;
        $this->direction = $direction;
    }

    public function origin(int $i) : float{
        return [$this->origin->getX(), $this->origin->getY(), $this->origin->getZ()][$i] ?? 0.001;
    }

    public function direction(int $i) : float{
        return [$this->direction->getX(), $this->direction->getY(), $this->direction->getZ()][$i] ?? 0.001;
    }

    public function getOrigin() : Vector3{
        return $this->origin;
    }

    public function getDirection() : Vector3{
        return $this->direction;
    }

}