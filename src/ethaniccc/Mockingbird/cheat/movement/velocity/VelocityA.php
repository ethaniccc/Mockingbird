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

namespace ethaniccc\Mockingbird\cheat\movement\velocity;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\LevelUtils;
use pocketmine\block\BlockIds;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\Player;
use pocketmine\event\player\PlayerDeathEvent;

class VelocityA extends Cheat{

    private $queuedMotion = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, ?array $settings){
        parent::__construct($plugin, $cheatName, $cheatType, $settings);
    }

    public function onMotion(EntityMotionEvent $event) : void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
            $name = $entity->getName();
            if(!isset($this->queuedMotion[$name])){
                $this->queuedMotion[$name] = [];
            }
            // the client isn't going to move with all of those move packets so we limit the maximum amount
            if(count($this->queuedMotion[$name]) === 10){
                array_shift($this->queuedMotion[$name]);
            }
            // rip performance
            $info = new \stdClass();
            $info->maxTime = (int) ($entity->getPing() / 50) + 2;
            $info->yMotion = $event->getVector()->getY();
            $info->timePassed = 0;
            $info->failedMovements = 0;
            $info->maxFailedMotion = 0;
            $this->queuedMotion[$name][] = $info;
        }
    }

    public function onMove(MoveEvent $event) : void{
        $player = $event->getPlayer();
        $user = $this->getPlugin()->getUserManager()->get($player);
        $name = $player->getName();
        if(!empty($this->queuedMotion[$name] ?? [])){
            if($event->getMode() !== MoveEvent::MODE_NORMAL){
                // remove everything in queue
                $this->queuedMotion[$name] = [];
                return;
            }
            $info = $this->queuedMotion[$name][0];
            $expectedYDelta = $info->yMotion;
            $maxTime = $info->maxTime;
            $deltaY = $event->getDistanceY();
            ++$info->timePassed;
            if($info->timePassed <= $maxTime){
                if($deltaY < $expectedYDelta * $this->getSetting("percentage")
                && !LevelUtils::hasBlockAbove($user)
                && !LevelUtils::isNearBlock($user, BlockIds::COBWEB)
                && !LevelUtils::isNearBlock($user, BlockIds::WATER)
                && !$user->getServerOnGround()){
                    ++$info->failedMovements;
                    $info->maxFailedMotion = $event->getDistanceY();
                }
            } else {
                $failedMovements = $info->failedMovements;
                if($failedMovements >= $maxTime){
                    $this->addPreVL($name);
                    if($this->getPreVL($name) >= 6){
                        $divisor = $info->maxFailedMotion / $expectedYDelta;
                        $this->fail($player, null, $this->formatFailMessage($this->basicFailData($player)), [], "$name: eD: $expectedYDelta, mD: {$info->maxFailedMotion}, d: $divisor");
                    }
                } else {
                    $this->lowerPreVL($name, 0);
                }
                $this->queuedMotion[$name][0] = null;
                array_shift($this->queuedMotion[$name]);
                if(!empty($this->queuedMotion[$name])){
                    // there is another motion in "queue" and we deal with that
                    // with the current deltaY stored in the MoveEvent with the next motion in queue
                    $this->onMove($event);
                }
            }
        }
    }

    public function onDeath(PlayerDeathEvent $event) : void{
        // remove everything in queue
        $this->queuedMotion[$event->getPlayer()->getName()] = [];
    }

}