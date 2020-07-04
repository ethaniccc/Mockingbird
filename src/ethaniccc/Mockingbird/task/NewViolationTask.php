<?php

namespace ethaniccc\Mockingbird\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class NewViolationTask extends AsyncTask{

    private $name;
    private $violations;

    public function __construct(string $name, float $violations){
        $this->name = $name;
        $this->violations = $violations;
    }

    public function onRun(){
        // This is such a messy hack around the "database is locked" thing, but oh well...
        $database = new \SQLite3('plugin_data/Mockingbird/CheatDataTemp.db');
        $originalContent = file_get_contents('plugin_data/Mockingbird/CheatData.db');
        file_put_contents('plugin_data/Mockingbird/CheatDataTemp.db', $originalContent);
        $newData = $database->prepare("INSERT OR REPLACE INTO cheatData (playerName, violations) VALUES (:playerName, :violations)");
        $newData->bindValue(":playerName", $this->name);
        $newData->bindValue(":violations", $this->violations);
        $newData->execute();
        $newContent = file_get_contents('plugin_data/Mockingbird/CheatDataTemp.db');
        file_put_contents('plugin_data/Mockingbird/CheatData.db', $newContent);
        $database->close();
        unlink('plugin_data/Mockingbird/CheatDataTemp.db');
    }

}