<?php

namespace ethaniccc\Mockingbird\task;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;

class NewViolationTask extends AsyncTask{

    private $name;
    private $violations;

    private $debugMessage = "Nothing seemed to go wrong.";

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
        if(!file_put_contents('plugin_data/Mockingbird/CheatData.db', $newContent)){
            $this->debugMessage = "Failed to put temp-database contents in used database.";
            if(file_exists('plugin_data/Mockingbird/CheatData.db')){
                // I'm not sure this will even work
                if(unlink('plugin_data/Mockingbird/CheatData.db')){
                    $this->debugMessage = "Old read-only database deleted.";
                    $newDatabase = new \SQLite3('plugin_data/Mockingbird/CheatData.db');
                    file_put_contents('plugin_data/Mockingbird/CheatData.db', $newContent);
                    $database->close();
                    unlink('plugin_data/Mockingbird/CheatDataTemp.db');
                } else {
                    $this->debugMessage = "Failed to delete the database, a reload of Mockingbird is needed.";
                }
            }
        } else {
            $database->close();
            unlink('plugin_data/Mockingbird/CheatDataTemp.db');
        }
    }

    public function onCompletion(Server $server){
        if($this->debugMessage !== "Nothing seemed to go wrong.") $server->getLogger()->debug($this->debugMessage);
        if($this->debugMessage === "Failed to delete the database, a reload of Mockingbird is needed."){
            $plugin = $server->getPluginManager()->getPlugin("Mockingbird");
            if($plugin !== null){
                $server->getPluginManager()->disablePlugin($plugin);
                $server->getLogger()->debug("Mockingbird was disabled, re-enabling...");
                $server->getPluginManager()->enablePlugin($plugin);
                $tempContent = file_get_contents('plugin_data/Mockingbird/CheatDataTemp.db');
                file_put_contents('plugin_data/Mockingbird/CheatData.db', $tempContent);
            } else {
                $server->getLogger()->debug("Mockingbird not found...?");
            }
        }
    }

}