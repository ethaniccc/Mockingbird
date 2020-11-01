<?php

namespace ethaniccc\Mockingbird\detections\custom{

    use ethaniccc\Mockingbird\detections\Detection;
    use ethaniccc\Mockingbird\user\User;
    use pocketmine\network\mcpe\protocol\DataPacket;
    use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
    use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

    /**
     * Class TestModuleA
     * @package ethaniccc\Mockingbird\detections\custom
     * ---------------------------------------------------------------------------------------
     * This is an example of how to make a custom module, the namespace must be
     * declared as "ethaniccc\Mockingbird\detections\custom" to work, since that is
     * what Mockingbird will assume the namespace of the module is. When overriding
     * the "handle" function you can check if the packet given is an instance of a certain
     * packet and do your own magic from there. All custom modules must have a sub-type at the
     * end of the class name, in TestModuleA, the sub-type is "A"
     * ----------------------------------------------------------------------------------------
     * By default, custom detections will have punishments and suppression set to false, max violations
     * set to 25, and the punish type set to "kick". If you want to change this, while constructing, you can change
     * the properties that have these values set, as shown in __construct@TestModuleA.
     * ---------------------------------------------------------------------------------------
     * Ok now goodbye make your own modules because I suck at anti-cheats xd
     */
    class TestModuleA extends Detection{

        public function __construct(string $name, ?array $settings){
            parent::__construct($name, $settings);
            $this->maxVL = 100;
            $this->punishable = true;
            $this->suppression = true;
        }

        public function handle(DataPacket $packet, User $user): void{
            if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
                // to whoever is reviewing my plugin - this custom module is NOT included in production
                /**
                 * It's magic time, do whatever you want here.
                 */
                $this->debug("Cps: " . $user->cps, false);
            }
        }

    }

}