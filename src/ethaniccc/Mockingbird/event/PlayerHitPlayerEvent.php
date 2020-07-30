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
use pocketmine\Player;

class PlayerHitPlayerEvent extends PlayerEvent{

    /** @var Player */
    private $damaged;

    /**
     * PlayerHitPlayerEvent constructor.
     * @param Player $damager
     * @param Player $damaged
     */
    public function __construct(Player $damager, Player $damaged){
        $this->player = $damager;
        $this->damaged = $damaged;
    }

    /**
     * @return Player
     */
    public function getPlayerHit() : Player{
        return $this->damaged;
    }

    /**
     * @return Player
     */
    public function getDamager() : Player{
        return $this->player;
    }

    /**
     * @return float
     */
    public function getVectorDistance() : float{
        return $this->player->distance($this->damaged);
    }

    /**
     * @return float
     */
    public function getVectorDistanceXZ() : float{
        $damagerVector = clone $this->player->asVector3();
        $damagedVector = clone $this->damaged->asVector3();
        $xDist = $damagerVector->getX() - $damagedVector->getX();
        $zDist = $damagerVector->getZ() - $damagedVector->getZ();
        $distanceSquared = ($xDist * $xDist) + ($zDist * $zDist);
        return sqrt($distanceSquared);
    }

    /**
     * @return float
     */
    public function getAngle() : float{
        $damagerDirectionVector = clone $this->player->getDirectionVector();
        $damagerDirectionVector->y = 0;
        $damagerDirectionVector = $damagerDirectionVector->normalize();

        $damagedPos = clone $this->damaged->asVector3();
        $damagedPos->y = 0;

        $damagerPos = clone $this->player->asVector3();
        $damagerPos->y = 0;

        $distDiff = $damagedPos->subtract($damagerPos)->normalize();
        $dotResult = $damagerDirectionVector->dot($distDiff);

        return rad2deg(acos($dotResult));
    }

}