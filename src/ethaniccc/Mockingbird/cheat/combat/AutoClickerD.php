<?php

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use ethaniccc\Mockingbird\Mockingbird;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;

class AutoClickerD extends Cheat{

    private $cps = [];
    private $clicks = [];
    private $suspicionLevel = [];

    public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, false);
        $this->getServer()->getLogger()->debug("AutoClickerD is an inaccurate check and has been disabled.");
    }

    public function receivePacket(DataPacketReceiveEvent $event) : void{
        $packet = $event->getPacket();
        if($packet instanceof InventoryTransactionPacket){
            if($packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) $this->macroCheck($event->getPlayer());
        } elseif($packet instanceof LevelSoundEventPacket){
            if($packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) $this->macroCheck($event->getPlayer());
        } elseif($packet instanceof PlayerActionPacket){
            if($packet->action === PlayerActionPacket::ACTION_START_BREAK) $this->macroCheck($event->getPlayer());
        }
    }

    private function macroCheck(Player $player) : void{
        $name = $player->getName();
        if(!isset($this->cps[$name])) $this->cps[$name] = [];
        array_unshift($this->cps[$name], microtime(true));
        if(count($this->cps[$name]) >= 100){
            array_pop($this->cps[$name]);
        }
        if(empty($this->cps[$name])) return;
        $deltaTime = 1.0;
        $currentTime = microtime(true);
        $cps = (int) round(count(array_filter($this->cps[$name], static function(float $t) use ($deltaTime, $currentTime) : bool{
                return ($currentTime - $t) <= $deltaTime;
        })) / $deltaTime, 1);
        if(!isset($this->clicks[$name])) $this->clicks[$name] = [];
        array_push($this->clicks[$name], $cps);
        if(count($this->clicks[$name]) === 25){
            if(!isset($this->suspicionLevel[$name])) $this->suspicionLevel[$name] = 0;
            $minCps = min($this->clicks[$name]);
            $maxCps = max($this->clicks[$name]);
            if($maxCps - $minCps >= 4){
                $this->suspicionLevel[$name] += 1;
                if($this->suspicionLevel[$name] >= 4){
                    $this->addViolation($name);
                    $data = [
                        "VL" => $this->getCurrentViolations($name),
                        "Ping" => $player->getPing()
                    ];
                    $this->notifyStaff($name, $this->getName(), $data);
                    $this->suspicionLevel[$name] = 1;
                }
            } elseif($maxCps - $minCps >= 2 && $maxCps - $minCps < 4){
                $middleCps = round(array_sum($this->clicks[$name]) / count($this->clicks[$name]), 0, PHP_ROUND_HALF_DOWN);
                $middleCount = array_count_values($this->clicks[$name])[$middleCps];
                $this->getServer()->broadcastMessage("Click Values: " . implode(", ", $this->clicks[$name]) . "\nMiddle CPS: $middleCps\nThe middle CPS appears $middleCount times.");
                if(array_count_values($this->clicks[$name])[$middleCps] > ($maxCps - $minCps) / 0.2 && array_count_values($this->clicks[$name])[$minCps] > ($maxCps - $minCps) / 0.5){
                    $this->suspicionLevel[$name] += 1;
                    if($this->suspicionLevel[$name] >= 4){
                        $this->addViolation($name);
                        $data = [
                            "VL" => $this->getCurrentViolations($name),
                            "Ping" => $player->getPing()
                        ];
                        $this->notifyStaff($name, $this->getName(), $data);
                        $this->suspicionLevel[$name] = 1;
                    }
                }
            } else {
                $this->suspicionLevel[$name] *= 0.75;
            }
            unset($this->clicks[$name]);
            $this->clicks[$name] = [];
        }
    }

}