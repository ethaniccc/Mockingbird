<?php

namespace ethaniccc\Mockingbird\cheat;

interface Blatant{

    /**
     * @return mixed
     */
    public function getMaxViolations();

    /**
     * @param int $violations
     * @return mixed
     */
    public function setMaxViolations(int $violations);

    /**
     * @param string $name
     * @return mixed
     */
    public function resetBlatantViolations(string $name);

}