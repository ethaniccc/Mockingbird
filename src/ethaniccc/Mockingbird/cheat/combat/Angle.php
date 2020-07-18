<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\Player;

class Angle extends Cheat implements Blatant{

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(10);
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        if($event instanceof EntityDamageByChildEntityEvent){
            return;
        }
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damaged instanceof Player || !$damager instanceof Player){
            return;
        }

        $damagerDirectionVector = $damager->getDirectionVector();

        $damagerDirectionVector->y = 0;
        $damagerDirectionVector = $damagerDirectionVector->normalize();

        $damagedPos = $damaged->asVector3();
        $damagedPos->y = 0;

        $damagerPos = $damager->asVector3();
        $damagerPos->y = 0;

        $distDiff = $damagedPos->subtract($damagerPos)->normalize();
        $dotResult = $damagerDirectionVector->dot($distDiff);

        // props to the internet but i should prob figure out what this does lmao
        $angle = rad2deg(acos($dotResult));
        if($damagerPos->distance($damagedPos) > 0.75){
            if(round($angle, 0) > 110 && round($angle, 0) < 150){
                $this->addViolation($damager->getName());
                $data = [
                    "VL" => self::getCurrentViolations($damager->getName()),
                    "Angle" => round($angle, 0)
                ];
                $this->notifyStaff($damager->getName(), $this->getName(), $data);
            } elseif(round($angle, 0) >= 150){
                $this->addViolation($damager->getName());
                $this->punish($damager->getName());
            }
        }

    }

}