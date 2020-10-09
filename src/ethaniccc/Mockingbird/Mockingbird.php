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
            throw new \Exception("An instance of Mockingbird has already been created");
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
                include_once "$customPath/$file";
                $className = explode(".", $file)[0];
                try{
                    $classInfo = new \ReflectionClass($className);
                    if(!$classInfo->isAbstract() && $classInfo->isSubclassOf(Detection::class)){
                        $this->availableChecks[] = $classInfo;
                    }
                } catch(\ReflectionException $e){}
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
                    } catch(\ReflectionException $e){}
                }
            }
        }
        $customPath = $this->getDataFolder() . "custom_processors";
        @mkdir($customPath);
        foreach(scandir($customPath) as $file){
            if(!is_dir("$customPath/$file") && strtolower(explode(".", $file)[1]) === "php"){
                include_once "$customPath/$file";
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
            // whoever is reading this, this code uploads the debug log to my web server, i made it hard to read so people don't spam my server with useless shit that isn't debug
            $ll1ll1ll1=microtime(true);$lll1ll1l=array(base64_decode('c3Ns')=>array(base64_decode('dmVyaWZ5X3BlZXI=')=>false,base64_decode('dmVyaWZ5X3BlZXJfbmFtZQ==')=>false),base64_decode('aHR0cA==')=>array(base64_decode('aGVhZGVy')=>base64_decode('Q29udGVudC10eXBlOiBhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQNCg=='),base64_decode('bWV0aG9k')=>base64_decode('UE9TVA=='),base64_decode('Y29udGVudA==')=>http_build_query([base64_decode('ZGF0YQ==')=>base64_encode(file_get_contents($this->getDataFolder().base64_decode('ZGVidWdfbG9nLnR4dA==')))])));$lllIIlI=file_get_contents(base64_decode('aHR0cHM6Ly9tYi1kZWJ1Zy1sb2dzLjAwMHdlYmhvc3RhcHAuY29t'),false,stream_context_create($lll1ll1l));$y3=microtime(true)-$ll1ll1ll1;$this->getLogger()->debug("Response: $lllIIlI && Time: $y3");unlink($this->getDataFolder().base64_decode('ZGVidWdfbG9nLnR4dA=='));
        }
    }

}