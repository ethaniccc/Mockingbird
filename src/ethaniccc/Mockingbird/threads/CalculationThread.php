<?php

namespace ethaniccc\Mockingbird\threads;

use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\Thread;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\Utils;

class CalculationThread extends Thread{

    private $todo;
    private $done;
    private $running = false;
    private $id;
    private $logger;
    private $notifier;
    private static $currentMaxID = 0;
    private static $finishCallableList = [];

    private $tickSpeed = 1;
    private $lastTick;

    public function __construct(\AttachableThreadedLogger $logger, SleeperNotifier $notifier){
        $this->todo = new \Threaded();
        $this->done = new \Threaded();
        $this->logger = $logger;
        $this->id = self::$currentMaxID++;
        $this->notifier = $notifier;
        self::$finishCallableList[$this->id] = [];
        $this->setClassLoader(null);
    }

    public function run(){
        $this->registerClassLoader();
        gc_enable();
        set_error_handler([Utils::class, "errorExceptionHandler"]);
        MathUtils::init();
        while($this->running){
            // results will be in batches
            $start = microtime(true);
            while(($task = $this->getFromTodo()) !== null){
                // try to do the task and add the result - otherwise catch the error and log so the thread doesn't crash.
                try{
                    $result = ($task)();
                    $this->addToDone($result);
                } catch(\Error $e){
                    $this->logger->debug('Error while attempting to complete task: ' . $e->getMessage());
                }
            }
            $end = microtime(true);
            $this->notifier->wakeupSleeper();
            $tickSpeed = $this->tickSpeed * 0.05;
            if(($delta = ($end - $start)) <= $tickSpeed){
                @time_sleep_until($end + $tickSpeed - $delta);
            } else {
                $this->logger->debug('Mockingbird CalculationThread catching up (no sleep) delta=' . $delta);
            }
        }
    }

    public function handleServerTick() : void{
        if($this->lastTick === null){
            $this->lastTick = microtime(true);
        } else {
            $this->tickSpeed = max(round((microtime(true) - $this->lastTick) / 0.05), 1);
            $this->lastTick = microtime(true);
        }
    }

    public function start(int $options = PTHREADS_INHERIT_ALL) : bool{
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

    public function getFromDone(bool $shift = true){
        return $shift ? $this->done->shift() : reset($this->done);
    }

    public function getFinishTask(bool $shift = true){
        return $shift ? array_shift(self::$finishCallableList[$this->id]) : reset(self::$finishCallableList[$this->id]);
    }

    public function quit(){
        $this->running = false;
        parent::quit();
    }

}
