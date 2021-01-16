<?php

namespace ethaniccc\Mockingbird\detections\packet\badpackets;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

/**
 * Class BadPacketD
 * @package ethaniccc\Mockingbird\detections\packet\badpackets
 * BadPacketD checks if the player is gliding without having a valid Elytra in their inventory
 * to glide with. In rare cases, this check can false positive (see the T-O-D-O), but for the most case does not false.
 * This check was made to prevent fly disablers using PlayerActionPackets to tell the server it's gliding.
 */
class BadPacketD extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlSecondCount = 5;
        $this->lowMax = 0; $this->mediumMax = 2;
    }

    public function handleReceive(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            // the player is gliding without anything to glide with along with being off ground - invalid.
            // TODO: While testing ONCE, this check false flagged - make some hack to fix (fml).
            if($user->isGliding && $user->moveData->offGroundTicks >= 10 && $user->player->getArmorInventory()->getChestplate()->getId() !== ItemIds::ELYTRA){
                if(++$this->preVL >= 1.01){
                    $this->fail($user, "glide=true chestplate={$user->player->getArmorInventory()->getChestplate()->getId()}");
                }
            } else {
                $this->preVL = max($this->preVL - 0.05, 0);
            }
        }
    }

}