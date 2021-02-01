<?php

namespace ethaniccc\Mockingbird\tasks;

use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class BanTask extends Task{

    private $user;
    private $message;

    public function __construct(User $user, string $message){
        $this->user = $user;
        $this->message = $message;
    }

    public function onRun(int $currentTick){
        $player = $this->user->player;
        Server::getInstance()->getNameBans()->addBan($player->getName(), $this->message, null, "Mockingbird Anti-Cheat");
        $player->kick($this->message, false);
        UserManager::getInstance()->unregister($player);
    }

}