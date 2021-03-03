<?php

namespace ethaniccc\Mockingbird\handler;

use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

class NetworkStackLatencyHandler{

    private static $list = [];

    public static function random(bool $needsResponse = true) : NetworkStackLatencyPacket{
        $pk = new NetworkStackLatencyPacket();
        $pk->needResponse = $needsResponse; $pk->timestamp = mt_rand(1, 1000000000000000) * 1000;
        return $pk;
    }

    public static function send(User $user, NetworkStackLatencyPacket $packet, callable $onResponse) : void{
        if($packet->needResponse && $user->loggedIn){
            $timestamp = $packet->timestamp;
            $user->player->dataPacket($packet);
            if(!isset(self::$list[$user->hash])){
                self::$list[$user->hash] = [];
            }
            self::$list[$user->hash][$timestamp] = $onResponse;
        }
    }

    public static function execute(User $user, int $timestamp) : void{
        $closure = self::$list[$user->hash][$timestamp] ?? null;
        if($closure !== null){
            $closure($timestamp);
            unset(self::$list[$user->hash][$timestamp]);
        }
    }

    public static function remove(string $hash) : void{
        unset(self::$list[$hash]);
    }

}