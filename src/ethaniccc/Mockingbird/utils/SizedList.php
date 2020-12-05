<?php

namespace ethaniccc\Mockingbird\utils;

class SizedList{

    private $array = [];
    private $maxSize;

    public function __construct(int $maxSize = 20){
        $this->maxSize = $maxSize;
    }

    public function full() : bool{
        return count($this->array) === $this->maxSize;
    }

    public function add($val, $key = null) : void{
        $key === null ? $this->array[] = $val : $this->array[$key] = $val;
        if(count($this->array) > $this->maxSize){
            array_shift($this->array);
        }
    }

    public function size() : int{
        return count($this->array);
    }

    public function get() : array{
        return $this->array;
    }

}