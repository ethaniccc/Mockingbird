<?php

namespace ethaniccc\Mockingbird\user;

use pocketmine\Player;

class UserManager{

    private static $instance;
    private $users = [];

    public static function init() : void{
        if(self::$instance !== null){
            return;
        }
        self::$instance = new UserManager();
    }

    public static function getInstance() : ?UserManager{
        return self::$instance;
    }

    public function register(User $user) : void{
        if(isset($this->users[spl_object_hash($user->player)])){
            // destruct object, useless
            $this->users[spl_object_hash($user->player)] = null;
        }
        $this->users[spl_object_hash($user->player)] = $user;
    }

    public function get(Player $player) : ?User{
        return $this->users[spl_object_hash($player)] ?? null;
    }

    public function unregister(Player $player){
        unset($this->users[spl_object_hash($player)]);
    }

}