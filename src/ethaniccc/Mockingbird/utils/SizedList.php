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

    public function length() : int{
        return $this->maxSize;
    }

    public function size() : int{
        return count($this->array);
    }

    public function get($key){
        return $this->array[$key] ?? null;
    }

    public function getAll() : array{
        return $this->array;
    }

    public function clear() : void{
        $this->array = [];
    }

    public function minOrElse($fallback = null){
        return count($this->array) > 0 ? min($this->array) : $fallback;
    }

    public function maxOrElse($fallback = null){
        return count($this->array) > 0 ? max($this->array) : $fallback;
    }

    public function duplicates(int $sort = SORT_STRING) : int{
        return count($this->array) - count(array_unique($this->array, $sort));
    }

}