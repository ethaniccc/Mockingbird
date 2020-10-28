<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\user\UserManager;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;

class ReachA extends Detection{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 20;
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
            $estimatedTime = (microtime(true) * 1000) - $user->transactionLatency;
            $entity = $user->targetEntity;
            if($entity instanceof Player){
                $damagedUser = UserManager::getInstance()->get($entity);
                // get all the locations that are 100ms away from the estimated times to hopefully get one accurate location
                $possibleLocations = $damagedUser->locationHistory->getLocationsRelativeToTime($estimatedTime, 100);
                $distances = [];
                foreach($possibleLocations as $location){
                    $AABB = AABB::fromPosition($location)->expand(0.1, 0, 0.1);
                    $AABB->maxY = $AABB->minY + 1.9;
                    // 7 is the maximum reach in minecraft
                    if(($distance = $AABB->calculateInterceptedDistance($attackPos, $attackPos->add(MathUtils::directionVectorFromValues($user->yaw, $user->pitch)->multiply(7))))){
                        $distances[] = $distance;
                    }
                }
                if(!empty($distances)){
                    $distance = min($distances);
                    if($distance > 3.1){
                        if(++$this->preVL >= 10){
                            $this->fail($user, "distance=$distance probability={$this->getCheatProbability()}");
                            // this is to prevent the preVL raising too high
                            $this->preVL = min($this->preVL, 15);
                        }
                    } else {
                        $this->preVL -= $this->preVL > 0 ? 1 : 0;
                    }
                }
            }
        }
    }

}