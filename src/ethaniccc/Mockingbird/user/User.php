<?php

namespace ethaniccc\Mockingbird\user;

use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\utils\location\LocationHistory;
use pocketmine\Player;

class User{

    public $player;
    public $processors = [];
    public $checks = [];
    public $violations = [];
    public $loggedIn = false;
    public $isDesktop = false;

    public $alerts = false;
    public $debug = false;

    public $locationHistory;
    public $location, $lastLocation;
    public $moveDelta, $lastMoveDelta;
    public $clientOnGround, $serverOnGround;
    public $onGroundTicks = 0, $offGroundTicks = 0;
    public $lastOnGroundLocation;
    public $blockAbove, $blockBelow;
    public $currentMotion;

    public $cps, $clickTime;

    // attack pos is the position of the damager when attacking the targetEntity.
    public $attackPos;
    public $targetEntity;

    public $timeSinceTeleport = 0;
    public $timeSinceJoin = 0;
    public $timeSinceMotion = 0;
    public $timeSinceDamage = 0;
    public $timeSinceAttack = 0;

    public function __construct(Player $player){
        $this->player = $player;
        $this->locationHistory = new LocationHistory();
        foreach(Mockingbird::getInstance()->availableProcessors as $processorInfo){
            if($processorInfo instanceof \ReflectionClass){
                $this->processors[] = $processorInfo->newInstanceArgs([$this]);
            }
        }
        foreach(Mockingbird::getInstance()->availableChecks as $checkInfo){
            if($checkInfo instanceof \ReflectionClass){
                $this->checks[] = $checkInfo->newInstanceArgs([$checkInfo->getShortName(), Mockingbird::getInstance()->getConfig()->getNested($checkInfo->getShortName())]);
            }
        }
    }

}