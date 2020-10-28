<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;

abstract class Processor{

    protected $user;

    public function __construct(User $user){
        $this->user = $user;
    }

    public abstract function process(DataPacket $packet) : void;

}