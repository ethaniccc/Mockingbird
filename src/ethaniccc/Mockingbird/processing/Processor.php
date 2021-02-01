<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\event\Event;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class Processor{

    public abstract function process(DataPacket $packet, User $user) : void;

}