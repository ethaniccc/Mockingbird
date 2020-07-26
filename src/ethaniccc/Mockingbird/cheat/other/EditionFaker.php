<?php

namespace ethaniccc\Mockingbird\cheat\other;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\event\MoveEvent;
use ethaniccc\Mockingbird\Mockingbird;
use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\types\DeviceOS;

class EditionFaker extends Cheat implements Blatant{

    private $fakers = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(1);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof LoginPacket){
            try {
                $data = $packet->chainData;
                $parts = explode(".", $data['chain'][2]);

                $jwt = json_decode(base64_decode($parts[1]), true);
                $id = $jwt['extraData']['titleId'];
            } catch(\Exception $e){
                $id = -1;
            }

            $pc = !in_array($id, ["1739947436", "1810924247"]);

            $givenOS = $packet->clientData["DeviceOS"] ?? DeviceOS::UNKNOWN;
            $desktop = !in_array((int) $givenOS, [DeviceOS::ANDROID, DeviceOS::IOS]);

            if(!$desktop && $pc){
                $this->fakers[spl_object_hash($event->getPlayer())] = 0;
            }

        }
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        $name = spl_object_hash($event->getPlayer());
        if(isset($this->fakers[$name])){
            unset($this->fakers[$name]);
            $this->addViolation($event->getPlayer()->getName());
        }
    }

}