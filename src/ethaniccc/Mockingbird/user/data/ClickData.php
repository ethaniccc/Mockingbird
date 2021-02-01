<?php

namespace ethaniccc\Mockingbird\user\data;

use ethaniccc\Mockingbird\utils\EvictingList;

class ClickData{

    public $timeSamples;
    public $tickSamples;
    public $tickSpeed = 0;
    public $timeSpeed = 0;
    public $cps = 0;

    public function __construct(){
        $this->tickSamples = new EvictingList(150);
        $this->timeSamples = new EvictingList(150);
    }

    public function getTickSamples(int $samples) : array{
        // get the last $samples samples of the sample array or get the most amount of samples possible
        $arr = $this->tickSamples->getAll();
        $offset = (count($arr)) >= $samples ? count($arr) - $samples : 0;
        return array_slice($arr, $offset, $samples);
    }

    public function getTimeSamples(int $samples) : array{
        // get the last $samples samples of the sample array or get the most amount of samples possible
        $arr = $this->timeSamples->getAll();
        $offset = (count($arr)) >= $samples ? count($arr) - $samples : 0;
        return array_slice($arr, $offset, $samples);
    }

}