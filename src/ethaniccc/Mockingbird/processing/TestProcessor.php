<?php

namespace ethaniccc\Mockingbird\processing;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\network\mcpe\protocol\DataPacket;

class TestProcessor extends Processor{

    /** @var EvictingList */
    private $locations;

    public function __construct(){
        // client interpolates over 3 locations
        $this->locations = new EvictingList(3);
    }

    public function process(DataPacket $packet, User $user): void{
        // this is where I go to do super duper secret stuff - no secret stuff 4u m8 :)
        // however, you can have the smallest peak at what I was working on...
        // WHOEVER IS READING THIS: Please note that this processor isn't used anywhere
        // in release and is meant as a place for me to do... stuff
    }

}