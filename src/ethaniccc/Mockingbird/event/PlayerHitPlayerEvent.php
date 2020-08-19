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
    
    private $attackCoolDown;
  
    /**
     * PlayerHitPlayerEvent constructor.
     * @param Player $damager
     * @param Player $damaged
     * @param int $attackCoolDown
     */
    public function __construct(Player $damager, Player $damaged,int $attackCoolDown){
        $this->player = $damager;
        $this->damaged = $damaged;
        $this->attackCoolDown = $attackCoolDown;
    }

    public function getPlayerHit() : Player{
        return $this->damaged;
    }

    public function getDamager() : Player{
        return $this->player;
    }

    public function getAttackCoolDown() : int{
        return $this->attackCoolDown;
    }

    public function getVectorDistance() : float{
        return $this->player->distance($this->damaged);
    }

    public function getVectorDistanceXZ() : float{
        $damagerVector = clone $this->player->asVector3();
        $damagedVector = clone $this->damaged->asVector3();
        $xDist = $damagerVector->getX() - $damagedVector->getX();
        $zDist = $damagerVector->getZ() - $damagedVector->getZ();
        $distanceSquared = ($xDist * $xDist) + ($zDist * $zDist);
        return sqrt($distanceSquared);
    }

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
