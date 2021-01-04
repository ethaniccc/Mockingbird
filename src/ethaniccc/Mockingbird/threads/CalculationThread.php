<?php

namespace ethaniccc\Mockingbird\threads;

use pocketmine\snooze\SleeperNotifier;
use pocketmine\Thread;

class CalculationThread extends Thread{

    private $todo;
    private $done;
    private $notifier;
    private $running = false;
    private $id;
    private static $currentMaxID = 0;
    private static $finishCallableList = [];

    public function __construct(SleeperNotifier $notifier){
        $this->notifier = $notifier;
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
            while(($task = $this->getFromTodo()) !== null){
                // do the task and add the result
                $this->addToDone(($task)());
            }
            // notify the main thread of completion so it can run all the finish tasks along with results
            $this->notifier->wakeupSleeper();
            // sleep for two ticks
            usleep(100000);
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

    public function quit(){
        $this->running = false;
        parent::quit();
    }

}