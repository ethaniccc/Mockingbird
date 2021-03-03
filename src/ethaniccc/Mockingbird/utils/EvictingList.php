<?php

namespace ethaniccc\Mockingbird\utils;

class EvictingList{

    private $array = [];
    private $maxSize;

    public static function fromArray(array $arr) : self{
        $list = new self(count($arr));
        $list->array = $arr;
        return $list;
    }

    public function __construct(int $maxSize = 20){
        $this->maxSize = $maxSize;
    }

    public function full() : bool{
        return count($this->array) === $this->maxSize;
    }

    public function add($val, $key = null) : EvictingList{
        $key === null ? $this->array[] = $val : $this->array[$key] = $val;
        if(count($this->array) > $this->maxSize){
            array_shift($this->array);
        }
        return $this;
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

    public function shift(){
        return array_shift($this->array);
    }

    public function reverse(bool $referenced = false) : EvictingList{
        if($referenced){
            $this->array = array_reverse($this->array);
            return $this;
        } else {
            return self::fromArray(array_reverse($this->array));
        }
    }

}