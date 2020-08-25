<?php

namespace ethaniccc\Mockingbird\utils\user;

use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;

class UserManager{

    private $users = [];

    public function get(Player $player) : ?User{
        return $this->users[spl_object_hash($player)] ?? null;
    }

    public function register(Player $player, bool $isMobile, LoginPacket $packet) : void{
        $this->users[spl_object_hash($player)] = new User($player, $isMobile, $packet);
    }

    public function unregister(Player $player) : void{
        unset($this->users[spl_object_hash($player)]);
    }

}