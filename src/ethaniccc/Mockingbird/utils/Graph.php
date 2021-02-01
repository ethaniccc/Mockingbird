<?php

namespace ethaniccc\Mockingbird\utils;

final class Graph{

    private $positives, $negatives;

    public function __construct(int $positives, int $negatives){
        [$this->positives, $this->negatives] = [$positives, $negatives];
    }

    public function getPositives() : int{
        return $this->positives;
    }

    public function getNegatives() : int{
        return $this->negatives;
    }

    public static function fromValues(array $nums) : self{
        $largest = max($nums);
        [$height, $positives, $negatives] = [2, 0, 0];
        for($i = $height - 1; $i > 0; --$i){
            foreach($nums as $num){
                $value = $height * $num / $largest;
                if($value > $i && $value < $i + 1){
                    ++$positives;
                } else {
                    ++$negatives;
                }
            }
        }
        return new self($positives, $negatives);
    }

}