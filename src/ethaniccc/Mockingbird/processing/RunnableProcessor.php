<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class RunnableProcessor extends Processor{

    public function process(DataPacket $packet, User $user) : void{
    }

    abstract public function run(User $user) : void;

}