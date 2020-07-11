<?php

namespace ethaniccc\Mockingbird\cheat;

interface StrictRequirements{

    /**
     * @param float $tps
     * @return mixed
     */
    public function setRequiredTPS(float $tps);

    /**
     * @return mixed
     */
    public function getRequiredTPS();

    /**
     * @param int $ping
     * @return mixed
     */
    public function setRequiredPing(int $ping);

    /**
     * @return mixed
     */
    public function getRequiredPing();
}