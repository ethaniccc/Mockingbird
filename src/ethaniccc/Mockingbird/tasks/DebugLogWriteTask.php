<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;

class DebugLogWriteTask extends AsyncTask{

    private $debugMessage = "";
    private $debugLogPath;
    private $time;

    public function __construct(string $debugLogPath){
        $this->debugLogPath = $debugLogPath;
        $this->time = microtime(true);
    }

    public function addData(string $data) : void{
        $this->debugMessage .= "$data\n";
    }

    public function onRun(){
        if($this->debugMessage !== ""){
            $log = @fopen($this->debugLogPath, "a");
            @fwrite($log, $this->debugMessage);
            @fclose($log);
        }
    }

}