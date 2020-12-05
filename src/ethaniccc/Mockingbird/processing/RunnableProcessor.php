<?php

namespace ethaniccc\Mockingbird\processing;

use pocketmine\network\mcpe\protocol\DataPacket;

abstract class RunnableProcessor extends Processor{

    public function process(DataPacket $packet) : void{
    }

    abstract public function run() : void;

}