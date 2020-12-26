<?php

namespace ethaniccc\Mockingbird\threads;

use pocketmine\Server;
use pocketmine\snooze\SleeperNotifier;

class ThreadManager{

    /** @var BaseThread[] */
    private $threads = [];
    /** @var SleeperNotifier */
    private $notifier;

    public function __construct(){
        $this->notifier = new SleeperNotifier();
        Server::getInstance()->getTickSleeper()->addNotifier($this->notifier, function() : void{
            foreach($this->threads as $key => $thread){
                if($thread->isFinished()){
                    $thread->quit();
                    unset($this->threads[$key]);
                }
            }
        });
    }

    public function addThread(BaseThread &$thread, int $option = PTHREADS_INHERIT_ALL) : void{
        $this->threads[$thread->getID()] = $thread;
        $thread->start($option);
    }

    public function getNotifier() : SleeperNotifier{
        return $this->notifier;
    }

    public function stopAll() : void{
        foreach($this->threads as $thread){
            $thread->forceQuit();
        }
        Server::getInstance()->getTickSleeper()->removeNotifier($this->notifier);
    }

}