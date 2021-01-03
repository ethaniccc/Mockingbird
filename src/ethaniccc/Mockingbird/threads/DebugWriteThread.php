<?php

namespace ethaniccc\Mockingbird\threads;

use pocketmine\Thread;

class DebugWriteThread extends Thread{

    private $running;
    private $debugMessage = '';
    private $debugPath;

    public function __construct(string $debugPath){
        $this->debugPath = $debugPath;
    }

    public function addData(string $data) : void{
        $this->debugMessage .= $data . PHP_EOL;
    }

    public function run(){
        $this->running = true;
        while($this->running){
            if($this->debugMessage !== ''){
                $log = @fopen($this->debugPath, 'a');
                @fwrite($log, $this->debugMessage);
                @fclose($log);
                $this->debugMessage = '';
            }
            sleep(3);
        }
    }

    public function quit(){
        $this->running = false;
        parent::quit();
    }

}