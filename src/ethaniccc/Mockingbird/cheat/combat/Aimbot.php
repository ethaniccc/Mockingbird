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

namespace ethaniccc\Mockingbird\cheat\combat;

use ethaniccc\Mockingbird\cheat\Cheat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\plugin\Plugin;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

class Aimbot extends Cheat{

    private $cheatedHit = [];

    public function __construct(Plugin $plugin, string $cheatName, string $cheatType, bool $enabled = true){
        parent::__construct($plugin, $cheatName, $cheatType, $enabled);
    }

    public function onEvent(EntityDamageByEntityEvent $event){
        $damager = $event->getDamager();
        $damaged = $event->getEntity();
        if(!$damager instanceof Player) return;
        $name = $damager->getName();
        if($this->aimbotLook($damager, $damaged)){
            if(!isset($this->cheatedHit[$name])) $this->cheatedHit[$name] = [];
            array_push($this->cheatedHit[$name], "illegal");
        } else {
            if(!isset($this->cheatedHit[$name])) $this->cheatedHit[$name] = [];
            array_push($this->cheatedHit[$name], "legitimate");
        }

        if(count($this->cheatedHit[$name]) === 10){
            $hitTypes = array_count_values($this->cheatedHit[$name]);
            asort($hitTypes);
            $commonHitType = array_slice(array_keys($hitTypes), 0, 5, true);
            if($commonHitType[0] === "illegal"){
                $this->addViolation($name);
                $data = [
                    "VL" => $this->getCurrentViolations($damager->getName()),
                    "Ping" => $damager->getPing()
                ];
                $this->notifyStaff($name, $this->getName(), $data);
            }
            unset($this->cheatedHit[$name]);
            $this->cheatedHit[$name] = [];
        }
    }

    private function aimbotLook(Entity $damager, Entity $target) : bool{
        $horizontal = sqrt(($target->getX() - $damager->getX()) ** 2 + ($target->getZ() - $damager->getZ()) ** 2);
        $vertical = $target->getY() - $damager->getY();

        $pitch = -atan2($vertical, $horizontal) / M_PI * 180;

        $xDist = $target->getX() - $damager->getX();
        $zDist = $target->getZ() - $damager->getZ();
        
        $yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
        if($damager->getYaw() < 0){
			$yaw += 360.0;
        }

        /** AIMBOT TESTS **/
        /* Expected Pitch, Given Pitch, Difference ABS || Expected Yaw, Given Yaw, Difference ABS */
        /* OnGround Same Y Results */
        // Test 1: 0, -2, 2 || -83, 277, 360
        // Test 2: 0, -2, 2 || -70, 289, 367
        // Test 3: 0, -2, 2 || 54, 55, 1
        // Result: Pitch always seems to have a difference of 2, no constant in yaw.
        /* OnGround Different Y Results */
        // Test 1: -25, -27, 2 || -146, 214, 360
        // Test 2: -32, -34, 2 || 6, 5, 1
        // Test 3: -22, -24, 2 || -172, 187, 359
        // Result: Pitch always seems to have a difference of 2, no constant in yaw.
        /* !OnGround Y - 1 Results */
        // Test 1: 38, 36, 2 || -71, 288, 359
        // Test 2: 46, 44, 2 || -1, 359, 360
        // Test 3: 33, 31, 2 || -118, 242, 360
        /* !OnGround Y + 1 Results */
        // Test 1: -5, -1, 4 || -150, 210, 360
        // Test 2: 1, -1, 2 || -150, 210, 360
        // Test 3: -4, -1, 5 || -80, 280, 360
        // Test 4: 4, 3, 1 || -146, 214, 360

        //var_dump($pitch, $yaw, $damager->getPitch(), $damager->getYaw());
        if($damager->isOnGround()){
            return abs(round($pitch) - round($damager->getPitch())) == 2;
        } else {
            return round($damager->getY()) - round($target->getY()) <= -1 && round($damager->getY()) - round($target->getY()) != 0 ? abs(round($pitch) - round($damager->getPitch())) == 2 : abs(round($yaw) - round($damager->getYaw())) == 360;
        }
    }

}