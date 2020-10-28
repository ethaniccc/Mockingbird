<?php

namespace ethaniccc\Mockingbird\packets;

use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;

class MotionPacket extends DataPacket{

    public $motionX;
    public $motionY;
    public $motionZ;

    public function __construct(EntityMotionEvent $event){
        $motion = $event->getVector();
        $this->motionX = $motion->x;
        $this->motionY = $motion->y;
        $this->motionZ = $motion->z;
    }

    public function handle(NetworkSession $session): bool{
        return true;
    }

}