<?php

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\TextFormat;

class Speed extends Cheat{

    private const MAX_SPEED = 0.28;
    private $speedDiff = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onMove(PlayerMoveEvent $event) : void{

        $player = $event->getPlayer();
        $name = $player->getName();

        $from = $event->getFrom();
        $to = $event->getTo();

        $distX = ($to->x - $from->x);
        $distZ = ($to->z - $from->z);
        if($distX == 0 && $distZ == 0){
            return;
        } elseif($distX === 0 && $distZ !== 0){
            $distance = abs($distZ);
        } elseif($distZ === 0 && $distX !== 0){
            $distance = abs($distX);
        } else {
            // Let's say we have a right triangle and we need to find the slope
            // of the triangle - basiclly the Pythagorean Theorem.
            $distanceSquared = abs(($distX * $distX) + ($distZ * $distZ));
            $distance = sqrt($distanceSquared);
            // Sometimes this distance spikes due to lag around 2x the original speed.
            // Keep in mind to ignore those values.
        }
        $distance = round($distance, 2);
    }

}