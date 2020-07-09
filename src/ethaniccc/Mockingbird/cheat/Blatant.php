<?php

namespace ethaniccc\Mockingbird\cheat;

interface Blatant{

    public function getMaxViolations();

    public function setMaxViolations(int $violations);

    public function resetBlatantViolations(string $name);

}