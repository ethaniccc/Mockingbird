<?php

namespace ethaniccc\Mockingbird\tasks;

use pocketmine\scheduler\AsyncTask;

class PacketLogWriteTask extends AsyncTask{
	public const SPLIT = '\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/';

	private string $path;
	private array $data;

	public function __construct(string $path, array $data){
		$this->path = $path . '.txt';
		$this->data = $data;
	}

	public function onRun() : void{
		@unlink($this->path);
		$data = '';
		foreach($this->data as $packet){
			$data .= var_export($packet, true) . PHP_EOL;
		}
		file_put_contents($this->path, $data);
	}
}