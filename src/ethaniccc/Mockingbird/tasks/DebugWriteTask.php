<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;

class DebugWriteTask extends AsyncTask{

    private $data = '';
    private $debugPath;
    private $shouldOverwrite = false;

    public function __construct(string $debugPath){
        $this->debugPath = $debugPath;
    }

    public function addData(string $data) : void{
        $this->data .= $data . PHP_EOL;
    }

    public function setShouldOverwrite(bool $val = true) : void{
        $this->shouldOverwrite = $val;
    }

    public function onRun(){
        if($this->data !== ''){
            if($this->shouldOverwrite){
                @file_put_contents($this->debugPath, $this->data);
            } else {
                $log = @fopen($this->debugPath, 'a');
                @fwrite($log, $this->data);
                @fclose($log);
                $this->data = '';
            }
        }
    }

}