<?php

namespace ethaniccc\Mockingbird\cheat;

interface StrictRequirements{

    public function setRequiredTPS(float $tps);

    public function getRequiredTPS();

    public function setRequiredPing(int $ping);

    public function getRequiredPing();
}