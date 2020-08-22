<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;
use Closure;
use pocketmine\Server;

class AsyncClosureTask extends AsyncTask{

    private $runClosure, $completeClosure;

    public function __construct(Closure $runClosure, ?Closure $completeClosure = null){
        $this->runClosure = $runClosure;
        $this->completeClosure = $completeClosure;
    }

    public function onRun(){
        $this->setResult(($this->runClosure)());
    }

    public function onCompletion(Server $server){
        if($this->completeClosure !== null){
            ($this->completeClosure)($server, $this->getResult());
        }
    }

}