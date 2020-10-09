<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;

class DebugLogWriteTask extends AsyncTask{

    private $debugMessage;
    private $debugLogPath;

    public function __construct(string $debugMessage, string $debugLogPath){
        $this->debugMessage = "$debugMessage\n";
        $this->debugLogPath = $debugLogPath;
    }

    public function onRun(){
        $log = @fopen($this->debugLogPath, "a");
        @fwrite($log, $this->debugMessage);
        @fclose($log);
    }

}