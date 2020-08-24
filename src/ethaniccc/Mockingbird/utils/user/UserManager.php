<?php

namespace ethaniccc\Mockingbird\utils\user;

use pocketmine\Player;

class UserManager{

    private $users = [];

    public function get(Player $player) : ?User{
        return $this->users[spl_object_hash($player)] ?? null;
    }

    public function register(Player $player, bool $isMobile) : void{
        $this->users[spl_object_hash($player)] = new User($player, $isMobile);
    }

    public function unregister(Player $player) : void{
        unset($this->users[spl_object_hash($player)]);
    }

}