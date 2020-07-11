<?php

// IMPORTANT: This check is not made by me, but by Bavfalcon9.
// You can check out Mavoric (another Anti-Cheat) here: https://github.com/Bavfalcon9/Mavoric/tree/v2.0.0
// This is to only demonstrate how to make a custom module.

namespace ethaniccc\Mockingbird\cheat\custom{

    use ethaniccc\Mockingbird\Mockingbird;
    use ethaniccc\Mockingbird\cheat\Cheat;
    use pocketmine\entity\Effect;
    use pocketmine\entity\EffectInstance;
    use pocketmine\event\player\PlayerMoveEvent;

    class MavoricSpeedA extends Cheat{

        private $lastMovements = [];

        public function __construct(Mockingbird $plugin, string $cheatName, string $cheatType, bool $enabled = true){
            parent::__construct($plugin, $cheatName, $cheatType, $enabled);
        }

        /**
         * @param PlayerMoveEvent $ev
         * @author Bavfalcon9
         * @commit 5fc9f89
         */
        public function onMove(PlayerMoveEvent $ev) : void{
            $player = $ev->getPlayer();
            $to = $ev->getTo();
            $from = $ev->getFrom();
            $effLevel = ($player->getEffect(Effect::SPEED) instanceof EffectInstance) ? $player->getEffect(Effect::SPEED)->getEffectLevel() : 0;
            $allowed = ($player->getPing() * 0.008) + 0.7 + ($effLevel * 0.4);
            $distX = (($from->x - $to->x) ** 2);
            $distZ = (($from->z - $to->z) ** 2);
            $lastMovementTick = $this->lastMovements[$player->getId()] ?? $this->getServer()->getTick();
            if (($this->getServer()->getTick() - $lastMovementTick) >= 2) {
                $this->lastMovements[$player->getId()] = $this->getServer()->getTick();
                return;
            }
            if (sqrt($distX + $distZ) > $allowed) {
                $this->addViolation($player->getName());
                $this->notifyStaff($player->getName(), $this->getName(), $this->genericAlertData($player));
            }
            $this->lastMovements[$player->getId()] = $this->getServer()->getTick();
        }

    }

}