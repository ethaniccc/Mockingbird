<?php

declare(strict_types=1);

namespace ethaniccc\Mockingbird;

use ethaniccc\Mockingbird\commands\ToggleAlertsCommand;
use ethaniccc\Mockingbird\commands\ToggleDebugCommand;
use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\listener\MockingbirdListener;
use ethaniccc\Mockingbird\processing\Processor;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\event\HandlerList;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Mockingbird extends PluginBase{

    private static $instance;
    public $availableChecks;
    public $availableProcessors;

    public static function getInstance() : Mockingbird{
        return self::$instance;
    }

    public function onEnable() : void{
        if(self::$instance !== null){
            return;
        }
        file_put_contents($this->getDataFolder() . "debug_log.txt", "");
        self::$instance = $this;
        UserManager::init();
        new MockingbirdListener();
        $this->getAvailableProcessors();
        $this->getAvailableChecks();
        $this->registerCommands();
    }

    public function getPrefix() : string{
        return $this->getConfig()->get("prefix") . TextFormat::RESET;
    }

    private function registerCommands() : void{
        $commands = [
            new ToggleAlertsCommand($this),
            new ToggleDebugCommand($this),
        ];
        $this->getServer()->getCommandMap()->registerAll($this->getName(), $commands);
    }

    private function getAvailableChecks() : void{
        $path = $this->getFile() . "src/ethaniccc/Mockingbird/detections";
        foreach(scandir($path) as $file){
            if(is_dir("$path/$file") && !in_array($file, [".", ".."])){
                $type = $file;
                foreach(scandir("$path/$file") as $otherFile){
                    if(is_dir("$path/$file/$otherFile") && !in_array($file, [".", ".."])){
                        $subType = $otherFile;
                        foreach(scandir("$path/$file/$otherFile") as $check){
                            if(!is_dir("$path/$file/$otherFile/$check")){
                                $extension = explode(".", $check)[1];
                                if(strtolower($extension) === "php"){
                                    $checkName = explode(".", $check)[0];
                                    $fullCheckName = "ethaniccc\\Mockingbird\\detections\\$type\\$subType\\$checkName";
                                    try{
                                        $classInfo = new \ReflectionClass($fullCheckName);
                                        if(!$classInfo->isAbstract() && $classInfo->isSubclassOf(Detection::class)){
                                            $this->availableChecks[] = $classInfo;
                                        }
                                    } catch(\ReflectionException $e){}
                                }
                            }
                        }
                    }
                }
            }
        }
        $customPath = $this->getDataFolder() . "custom_modules";
        @mkdir($customPath);
        foreach(scandir($customPath) as $file){
            if(!is_dir("$customPath/$file") && strtolower(explode(".", $file)[1]) === "php"){
                require_once "$customPath/$file";
                $className = explode(".", $file)[0];
                try{
                    $classInfo = new \ReflectionClass("ethaniccc\\Mockingbird\\detections\\custom\\$className");
                    if(!$classInfo->isAbstract() && $classInfo->isSubclassOf(Detection::class)){
                        $this->availableChecks[] = $classInfo;
                    }
                } catch(\ReflectionException $e){
                    $this->getLogger()->debug($e->getMessage());
                }
            }
        }
    }

    private function getAvailableProcessors() : void{
        $path = $this->getFile() . "src/ethaniccc/Mockingbird/processing";
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach($iterator as $fileInfo){
            if($fileInfo instanceof \SplFileInfo){
                if(!$fileInfo->isDir() && $fileInfo->isReadable() && strtolower($fileInfo->getExtension()) === "php"){
                    $className = str_replace(".php", "", $fileInfo->getFilename());
                    $fullClassName = "\\ethaniccc\\Mockingbird\\processing\\$className";
                    try{
                        $classInfo = new \ReflectionClass($fullClassName);
                        if(!$classInfo->isAbstract() && $classInfo->isSubclassOf(Processor::class)){
                            $this->availableProcessors[] = $classInfo;
                        }
                    } catch(\ReflectionException $e){
                        $this->getLogger()->debug($e->getMessage());
                    }
                }
            }
        }
        $customPath = $this->getDataFolder() . "custom_processors";
        @mkdir($customPath);
        foreach(scandir($customPath) as $file){
            if(!is_dir("$customPath/$file") && strtolower(explode(".", $file)[1]) === "php"){
                require_once "$customPath/$file";
                $className = explode(".", $file)[0];
                try{
                    $classInfo = new \ReflectionClass($className);
                    if(!$classInfo->isAbstract() && $classInfo->isSubclassOf(Processor::class)){
                        $this->availableProcessors[] = $classInfo;
                    }
                } catch(\ReflectionException $e){}
            }
        }
    }

    public function onDisable(){
        if($this->getConfig()->get("upload_debug")){
            $options = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
                "http" => array(
                    "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                    "method" => "POST",
                    "content" => http_build_query(["data" => base64_encode(file_get_contents($this->getDataFolder() . "debug_log.txt"))])
                )
            );
            $response = file_get_contents("https://mb-debug-logs.000webhostapp.com/", false, stream_context_create($options));
            $this->getLogger()->debug("Response: $response");
        }
    }

}