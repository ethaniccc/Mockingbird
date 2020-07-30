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

namespace ethaniccc\Mockingbird\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MoveEvent extends PlayerEvent{

    public const MODE_NORMAL = 0;
    public const MODE_RESET = 1;
    public const MODE_TELEPORT = 2;
    public const MODE_PITCH = 3;

    /** @var Vector3 */
    private $from;
    /** @var Vector3 */
    private $to;
    /** @var bool */
    private $onGround;
    /** @var int */
    private $mode = self::MODE_NORMAL;
    /** @var int */
    private $lastMovedTick = 0;

    /**
     * MoveEvent constructor.
     * @param Player $player
     * @param Vector3 $from
     * @param Vector3 $to
     * @param bool $onGround
     * @param int $mode
     */
    public function __construct(Player $player, Vector3 $from, Vector3 $to, bool $onGround, int $mode){
        $this->player = $player;
        $this->from = $from;
        $this->to = $to;
        $this->onGround = $onGround;
        $this->mode = $mode;

    }

    public function getMode() : int{
        return $this->mode;
    }

    /**
     * @return float
     */
    public function getDistance() : float{
        return $this->to->distance($this->from);
    }

    /**
     * @return float
     */
    public function getDistanceXZ() : float{
        $from = clone $this->from;
        $to = clone $this->to;

        $from->y = 0;
        $to->y = 0;

        return $to->distance($from);
    }

    /**
     * @return float
     */
    public function getDistanceY() : float{
        return $this->to->getY() - $this->from->getY();
    }

    /**
     * @return bool
     */
    public function onGround() : bool{
        return $this->onGround;
    }

    /**
     * @return Vector3
     */
    public function getFrom() : Vector3{
        return $this->from;
    }

    /**
     * @return Vector3
     */
    public function getTo() : Vector3{
        return $this->to;
    }

}