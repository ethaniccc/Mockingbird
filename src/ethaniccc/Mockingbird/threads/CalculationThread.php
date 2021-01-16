<?php

namespace ethaniccc\Mockingbird\threads;

use pocketmine\Thread;

class CalculationThread extends Thread{

    private $todo;
    private $done;
    private $running = false;
    private $id;
    private static $currentMaxID = 0;
    private static $finishCallableList = [];

    public function __construct(){
        $this->todo = new \Threaded();
        $this->done = new \Threaded();
        $this->id = self::$currentMaxID++;
        self::$finishCallableList[$this->id] = [];
        $this->setClassLoader(null);
    }

    public function run(){
        $this->registerClassLoader();
        while($this->running){
            // results will be in batches
            $start = microtime(true);
            while(($task = $this->getFromTodo()) !== null){
                // do the task and add the result
                $result = ($task)();
                $this->addToDone($result);
            }
            $end = microtime(true);
            $tickSpeed = 0.05;
            // if the run time is less than the tick speed then we can allow the thread to sleep,
            // otherwise, the thread is lacking behind and no sleeping for it until it gets the work done.
            if(($delta = $end - $start) < $tickSpeed){
                @time_sleep_until($end + $tickSpeed - $delta);
            }
        }
    }

    public function start(int $options = PTHREADS_INHERIT_ALL){
        $this->running = true;
        return parent::start($options);
    }

    /**
     * @param callable $do - The callable that should run on the separate thread
     * @param callable $finish - The callable that should run on the main thread.
     * This method should be called from the main thread ONLY.
     */
    public function addToTodo(callable $do, callable $finish) : void{
        $this->todo[] = $do;
        self::$finishCallableList[$this->id][] = $finish;
    }

    public function getFromTodo() : ?callable{
        return $this->todo->shift();
    }

    public function addToDone($val) : void{
        $this->done[] = $val;
    }

    public function getFromDone(){
        return $this->done->shift();
    }

    public function getFinishTask() : ?callable{
        return array_shift(self::$finishCallableList[$this->id]);
    }

    public function getAllDone() : \Threaded{
        return $this->done;
    }

    public function getAllFinishTasks() : array{
        return self::$finishCallableList[$this->id];
    }

    public function quit(){
        $this->running = false;
        parent::quit();
    }

}