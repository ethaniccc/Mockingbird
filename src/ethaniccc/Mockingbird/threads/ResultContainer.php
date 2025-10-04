<?php

namespace ethaniccc\Mockingbird\threads;

class ResultContainer{

    private $result;
    private $closure;
    private $id;

    public function __construct(callable $closure){
        $this->closure = $closure;
    }

    public function getID() : int{
        if($this->id === null){
            $this->id = mt_rand(1, 100000000000);
        }
        return $this->id;
    }

    public function getResult(){
        return $this->result;
    }

    public function setResult($result) : void{
        $this->result = $result;
    }

    public function run() : void{
        ($this->closure)($this->getResult());
    }

}