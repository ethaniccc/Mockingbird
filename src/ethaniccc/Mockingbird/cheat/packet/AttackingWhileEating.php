<?php

/*


$$\      $$\                     $$\       $$\                     $$\       $$\                 $$\
$$$\    $$$ |                    $$ |      \__|                    $$ |      \__|                $$ |
$$$$\  $$$$ | $$$$$$\   $$$$$$$\ $$ |  $$\ $$\ $$$$$$$\   $$$$$$\  $$$$$$$\  $$\  $$$$$$\   $$$$$$$ |
$$\$$\$$ $$ |$$  __$$\ $$  _____|$$ | $$  |$$ |$$  __$$\ $$  __$$\ $$  __$$\ $$ |$$  __$$\ $$  __$$ |
$$ \$$$  $$ |$$ /  $$ |$$ /      $$$$$$  / $$ |$$ |  $$ |$$ /  $$ |$$ |  $$ |$$ |$$ |  \__|$$ /  $$ |
$$ |\$  /$$ |$$ |  $$ |$$ |      $$  _$$<  $$ |$$ |  $$ |$$ |  $$ |$$ |  $$ |$$ |$$ |      $$ |  $$ |
$$ | \_/ $$ |\$$$$$$  |\$$$$$$$\ $$ | \$$\ $$ |$$ |  $$ |\$$$$$$$ |$$$$$$$  |$$ |$$ |      \$$$$$$$ |
\__|     \__| \______/  \_______|\__|  \__|\__|\__|  \__| \____$$ |\_______/ \__|\__|       \_______|
                                                         $$\   $$ |
                                                         \$$$$$$  |
                                                          \______/
~ Made by @ethaniccc idot </3
Github: https://www.github.com/ethaniccc
*/

namespace ethaniccc\Mockingbird\cheat\packet;

use ethaniccc\Mockingbird\cheat\Blatant;
use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;

class AttackingWhileEating extends Cheat implements Blatant{

    private $lastAttackTick = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        $this->setMaxViolations(5);
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $player = $event->getPlayer();
        $name = $player->getName();
        $packet = $event->getPacket();

        if($packet instanceof ActorEventPacket){
            if($packet->event === ActorEventPacket::EATING_ITEM){
                if(!isset($this->lastAttackTick[$name])){
                    return;
                }
                if($this->getServer()->getTick() - $this->lastAttackTick[$name] == 0){
                    $this->addViolation($name);
                }
            }
        }
    }

    public function onHit(EntityDamageByEntityEvent $event) : void{
        $damager = $event->getDamager();
        if(!$damager instanceof Player){
            return;
        }
        $this->lastAttackTick[$damager->getName()] = $this->getServer()->getTick();
    }

}