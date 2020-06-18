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

namespace ethaniccc\Mockingbird\cheat\movement;

use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class InventoryMove extends Cheat{

    private $inventoryOpen = [];

    public function __construct(Plugin $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(PlayerMoveEvent $event) : void{
        $name = $event->getPlayer()->getName();
        if(isset($this->inventoryOpen[$name])){
            if(microtime(true) - $this->inventoryOpen[$name] > 0.5){
                $this->addViolation($name);
                $data = [
                    "TPS" => $this->getServer()->getTicksPerSecond(),
                    "Ping" => $event->getPlayer()->getPing()
                ];
                $this->notifyStaff($name, $this->getName(), $data);
            }
        }
    }

    public function openInventory(InventoryOpenEvent $event) : void{
        $name = $event->getPlayer()->getName();
        if(!isset($this->inventoryOpen[$name])) $this->inventoryOpen[$name] = microtime(true);
    }

    public function closeInventory(InventoryCloseEvent $event) : void{
        $name = $event->getPlayer()->getName();
        if(isset($this->inventoryOpen[$name])) unset($this->inventoryOpen[$name]);
    }

}