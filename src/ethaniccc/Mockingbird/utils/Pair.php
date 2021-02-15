<?php

namespace ethaniccc\Mockingbird\utils;

class Pair{

    private $x, $y;

    public function __construct($x, $y){
        $this->x = $x; $this->y = $y;
    }

    public function getX(){
        return $this->x;
    }

    public function getY(){
        return $this->y;
    }

    public function setX($x) : void{
        $this->x = $x;
    }

    public function setY($y) : void{
        $this->y = $y;
    }

}