<?php

namespace ethaniccc\Mockingbird\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ethaniccc\Mockingbird\cheat\Cheat;

class SaveDataTask extends AsyncTask{

    private $saveData;
    private $dataFolder;

    public function __construct(string $dataFolder, bool $saveData){
        $this->saveData = $saveData;
        $this->dataFolder = $dataFolder;
    }

    public function onRun(){
        if($this->saveData){
            @mkdir($this->dataFolder . 'previous_data');
            $content = file_get_contents($this->dataFolder . 'CheatData.db');
            $count = count(scandir($this->dataFolder . 'previous_data')) - 2 + 1;
            $fileName = "OldData{$count}.db";
            file_put_contents($this->dataFolder . 'previous_data/' . $fileName, $content);
        }
        if(file_exists($this->dataFolder . 'CheatData.db')){
            unlink($this->dataFolder . 'CheatData.db');
        }
    }

}