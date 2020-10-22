<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;

class ReachA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof InventoryTransactionPacket
        && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY
        && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK
        && $user->isDesktop){
            $attackPos = $user->attackPos;
            if(!$attackPos instanceof Vector3){
                return;
            }
            $attackPos = $attackPos->subtract(0, 1.62, 0);
            $estimatedTime = (microtime(true) * 1000) - ($user->transactionLatency);
            $entity = $user->targetEntity;
            if($entity instanceof Player){
                $damagedUser = UserManager::getInstance()->get($entity);
                // get all the locations that are 100ms away from the estimated times to hopefully get one accurate location
                $possibleLocations = $damagedUser->locationHistory->getLocationsRelativeToTime($estimatedTime, 100);
                $distances = [];
                foreach($possibleLocations as $location){
                    $distances[] = MathUtils::vectorXZDistance($attackPos, $location) - 0.3;
                }
                if(!empty($distances)){
                    $distance = min($distances);
                    $this->debug("Distance: $distance", false);
                    if($distance >= $this->getSetting("max_reach")){
                        if(++$this->preVL >= 10){
                            $this->fail($user, "{$user->player->getName()}: d: $distance, pVL: {$this->preVL}");
                        }
                        $this->preVL = min(20, $this->preVL);
                    } else {
                        $this->preVL -= $this->preVL > 0 ? 1 : 0;
                        $this->reward($user, 0.999);
                    }
                }
            }
        }
    }

}