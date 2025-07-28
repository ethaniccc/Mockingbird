<?php

namespace ethaniccc\Mockingbird\detections\combat\reach;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\boundingbox\AABB;
use ethaniccc\Mockingbird\utils\boundingbox\Ray;
use ethaniccc\Mockingbird\utils\EvictingList;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\Server;

/**
 * Class ReachB
 * @package ethaniccc\Mockingbird\detections\combat\reach
 * ReachB uses raycasting to check if hits are legit. It interpolates between the player's positions/rotations
 * and casts rays from their eye position to see if they actually hit the target's bounding box.
 * It runs multiple raycasts per hit with different interpolation values and stores the hit distances.
 * If the minimum or maximum distances are too high and the buffer exceeds a level, flag.
 */
class ReachB extends Detection {
    private bool $run = false;
    private ?int $targetedEntityId = null;
    
    private float $interpolationStep = 0.1;
    private float $attackOffset = 0.0;
    
    private Vector3 $startAttackPos;
    private Vector3 $endAttackPos;
    
    private Vector3 $startEntityPos;
    private Vector3 $endEntityPos;
    
    private float $closestRawDistance = 0.0;
    private array $raycastResults = [];
    
    public function __construct(string $name, ?array $settings) {
        parent::__construct($name, $settings);
    }
    
    public function handleReceive(DataPacket $packet, User $user): void {
        if ($packet instanceof InventoryTransactionPacket) {
            $trData = $packet->trData;
            if (!($trData instanceof UseItemOnEntityTransactionData)) {
                return;
            }
            
            if ($trData->getActionType() !== UseItemOnEntityTransactionData::ACTION_ATTACK) {
                return;
            }
            
            if ($this->run) {
                return;
            }
            
            if ($user->player->isCreative() || $user->player->isSpectator()) {
                return;
            }
            
            $entity = Server::getInstance()->getWorldManager()->findEntity($trData->getActorRuntimeId());
            if ($entity === null) {
                return;
            }
            
             if ($entity->getTeleportationTicks() <= 20 || $user->getTeleportationTicks() <= 20) {
                 return;
             }
            
            $this->attackOffset = 1.62;
            if ($user->player->isSneaking()) {
                $this->attackOffset = 1.54;
            }
            
            $this->run = true;
            $this->targetedEntityId = $entity->getId();
            
            $this->startAttackPos = $user->moveData->lastLocation->add(0, $this->attackOffset, 0);
            $this->endAttackPos = $user->moveData->location->add(0, $this->attackOffset, 0);
            
            $this->startEntityPos = $entity->getPosition();
            $this->endEntityPos = $entity->getPosition();
            
            $bb1 = AABB::fromPosition($this->startEntityPos)->grow(0.1, 0.1, 0.1);
            $bb2 = AABB::fromPosition($this->endEntityPos)->grow(0.1, 0.1, 0.1);
            
            $distances = new EvictingList(4);
            $distances->add($bb1->distanceFromVector($this->startAttackPos));
            $distances->add($bb1->distanceFromVector($this->endAttackPos));
            $distances->add($bb2->distanceFromVector($this->startAttackPos));
            $distances->add($bb2->distanceFromVector($this->endAttackPos));
            
            $this->closestRawDistance = $distances->minOrElse(PHP_FLOAT_MAX);
            
            if ($this->closestRawDistance <= 3) {
                $this->violations = max($this->violations - 0.02, 0);
                return;
            }
            
            $this->preVL++;
            if ($this->preVL < 5) {
                return;
            }
            
            $this->fail($user, 
                "raw=" . round($this->closestRawDistance, 2),
                "raw=" . round($this->closestRawDistance, 2)
            );
            
        } elseif ($packet instanceof PlayerAuthInputPacket) {
            if (!$this->run) {
                return;
            }
            
            $this->run = false;
            
            if ($this->targetedEntityId === null) {
                return;
            }
            
            $entity = Server::getInstance()->getWorldManager()->findEntity($this->targetedEntityId);
            if ($entity === null) {
                return;
            }
            
             if ($packet->getInputMode() !== PlayerAuthInputPacket::INPUT_MODE_MOUSE) {
                return;
            }
            
            if ($user->player->isCreative() || $user->player->isSpectator()) {
                return;
            }
            
            $attackPosDelta = $this->endAttackPos->subtractVector($this->startAttackPos);
            $entityPosDelta = $this->endEntityPos->subtractVector($this->startEntityPos);

            $startYaw = $user->moveData->lastYaw;
            $startPitch = $user->moveData->lastPitch;
            $endYaw = $user->moveData->yaw;
            $endPitch = $user->moveData->pitch;
            
            $yawDelta = $endYaw - $startYaw;
            $pitchDelta = $endPitch - $startPitch;
            
            if (sqrt($yawDelta * $yawDelta + $pitchDelta * $pitchDelta) >= 180) {
                return;
            }
            
            $altEntityStartPos = $entity->getPosition();
            $altEntityEndPos = $entity->getPosition();
            $altEntityPosDelta = $altEntityEndPos->subtractVector($altEntityStartPos);
            
            $this->raycastResults = [];
            
            // Perform raycasting with interpolation
            for ($partialTicks = 0.0; $partialTicks <= 1.0; $partialTicks += $this->interpolationStep) {
                // Interpolate attack position
                $attackPos = $this->startAttackPos->addVector(
                    $attackPosDelta->multiply($partialTicks)
                );
                
                $entityPos = $this->startEntityPos->addVector(
                    $entityPosDelta->multiply($partialTicks)
                );
                $bb = AABB::fromPosition($entityPos)->grow(0.1, 0.1, 0.1);
                
                $currentYaw = $startYaw + ($yawDelta * $partialTicks);
                $currentPitch = $startPitch + ($pitchDelta * $partialTicks);
                
                $direction = $this->getDirectionVector($currentYaw, $currentPitch);
                $ray = new Ray($attackPos, $direction);
                
                // Check intersection
                $distance = $bb->collidesRay($ray, 0, 14);
                if ($distance > 0) {
                    $intersectionPoint = $attackPos->addVector($direction->multiply($distance));
                    $this->raycastResults[] = $attackPos->distance($intersectionPoint);
                }
                
                $entityPos = $altEntityStartPos->addVector(
                    $altEntityPosDelta->multiply($partialTicks)
                );
                $bb = AABB::fromPosition($entityPos)->grow(0.1, 0.1, 0.1);
                
                $distance = $bb->collidesRay($ray, 0, 14);
                if ($distance > 0) {
                    $intersectionPoint = $attackPos->addVector($direction->multiply($distance));
                    $this->raycastResults[] = $attackPos->distance($intersectionPoint);
                }
            }
            
            if (count($this->raycastResults) === 0) {
                return;
            }
            
            $minDist = 14.0;
            $maxDist = -1.0;
            
            foreach ($this->raycastResults as $result) {
                $minDist = min($minDist, $result);
                $maxDist = max($maxDist, $result);
            }
            
            if ($minDist <= 2.9 || $maxDist <= 3) {
                $this->preVL = max($this->preVL - 0.01, 0);
                if ($this->preVL <= 1) {
                    $this->violations = max($this->violations - 0.001, 0);
                }
                return;
            }
            
            $this->preVL++;
            if ($this->preVL < 5) {
                return;
            }
            
            $this->fail($user, 
                "min=" . round($minDist, 2) . " max=" . round($maxDist, 2),
                "min=" . round($minDist, 2) . " max=" . round($maxDist, 2)
            );
        }
    }
    
    public function handleSend(DataPacket $packet, User $user): void {
        
    }
    
    public function handleEvent(mixed $event, User $user): void {
        
    }
    
    private function getDirectionVector(float $yaw, float $pitch): Vector3 {
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        
        return new Vector3($x, $y, $z);
    }
}