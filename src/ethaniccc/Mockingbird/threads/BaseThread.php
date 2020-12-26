<?php

namespace ethaniccc\Mockingbird\threads;

use pocketmine\Thread;
use pocketmine\snooze\SleeperNotifier;

class BaseThread extends Thread{

    private $do;
    private $threadID;
    private $result, $serializedResult = false;
    private $notifier;
    private $finished = false;
    private static $currentID = 0;
    private static $finishList = [];

    public function __construct(SleeperNotifier $notifier, callable $do, callable $finish = null){
        $this->do = $do; $this->threadID = self::$currentID++;
        self::$finishList[$this->threadID] = $finish;
        $this->notifier = $notifier;
        $this->setClassLoader(null);
    }

    public function getID() : int{
        return $this->threadID;
    }

    public function setResult($result) : void{
        $this->result = ($this->serializedResult = !is_scalar($result)) ? serialize($result) : $result;
    }

    public function getResult(){
        return $this->serializedResult ? unserialize($this->result) : $this->result;
    }

    public function run(){
        $this->setResult(($this->do)());
        $this->finished = true;
        $this->notifier->wakeupSleeper();
    }

    public function quit(){
        parent::quit();
        $finishCallable = self::$finishList[$this->threadID];
        if($finishCallable !== null){
            ($finishCallable)($this->getResult());
        }
    }

    public function forceQuit(){
        parent::quit();
    }

    public function isFinished() : bool{
        return $this->finished;
    }

}