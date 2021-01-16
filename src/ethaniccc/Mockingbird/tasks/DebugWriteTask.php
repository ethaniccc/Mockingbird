<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;

class DebugWriteTask extends AsyncTask{

    private $data = '';
    private $debugPath;

    public function __construct(string $debugPath){
        $this->debugPath = $debugPath;
    }

    public function addData(string $data) : void{
        $this->data .= $data . PHP_EOL;
    }

    public function onRun(){
        if($this->data !== ''){
            $log = @fopen($this->debugPath, 'a');
            @fwrite($log, $this->data);
            @fclose($log);
            $this->data = '';
        }
    }

}