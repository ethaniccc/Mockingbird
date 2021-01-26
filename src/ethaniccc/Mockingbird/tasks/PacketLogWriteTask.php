<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\BinaryDataException;

class PacketLogWriteTask extends AsyncTask{

    public const SPLIT = '\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/';

    private $path;
    private $data;

    public function __construct(string $path, array $data){
        $this->path = $path . '.txt'; $this->data = $data;
    }

    public function onRun(){
        @unlink($this->path);
        $data = '';
        foreach($this->data as $packet){
            $data .= var_export($packet, true) . PHP_EOL;
        }
        file_put_contents($this->path, $data);
    }

}