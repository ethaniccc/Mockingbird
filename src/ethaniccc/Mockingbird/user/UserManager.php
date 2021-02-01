<?php

namespace ethaniccc\Mockingbird\user;

use pocketmine\entity\Entity;
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
        $key = spl_object_hash($user->player);
        if(isset($this->users[$key])){
            $this->users[$key] = null;
        }
        $this->users[$key] = $user;
    }

    public function get(Player $player) : ?User{
        return $this->users[spl_object_hash($player)] ?? null;
    }

    public function unregister(Player $player){
        unset($this->users[spl_object_hash($player)]);
    }

    /**
     * @return User[]
     */
    public function getUsers() : array{
        return $this->users;
    }

    public function getUserByName(string $name) : ?User{
        $found = null;
        $name = strtolower($name);
        $delta = PHP_INT_MAX;
        foreach($this->getUsers() as $user){
            if(stripos($user->player->getName(), $name) === 0){
                $curDelta = strlen($user->player->getName()) - strlen($name);
                if($curDelta < $delta){
                    $found = $user;
                    $delta = $curDelta;
                }
                if($curDelta === 0){
                    break;
                }
            }
        }
        return $found;
    }

}