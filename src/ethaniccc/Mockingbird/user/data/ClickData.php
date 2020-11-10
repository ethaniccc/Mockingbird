<?php

namespace ethaniccc\Mockingbird\user\data;

class ClickData{

    public $timeSamples = [];
    public $tickSamples = [];
    public $tickSpeed = 0;
    public $timeSpeed = 0;
    public $cps = 0;

    public function getTickSamples(int $samples) : array{
        // get the last $samples samples of the sample array or get the most amount of samples possible
        $offset = (count($this->tickSamples)) >= $samples ? count($this->tickSamples) - $samples : 0;
        return array_slice($this->tickSamples, $offset, $samples);
    }

    public function getTimeSamples(int $samples) : array{
        // get the last $samples samples of the sample array or get the most amount of samples possible
        $offset = (count($this->timeSamples)) >= $samples ? count($this->timeSamples) - $samples : 0;
        return array_slice($this->timeSamples, $offset, $samples);
    }

}