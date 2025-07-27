<?php

namespace YourNamespace\detections\combat\reach;

use YourNamespace\detections\Detection;
use YourNamespace\user\User;
use YourNamespace\utils\boundingbox\AABB;
use YourNamespace\utils\boundingbox\Ray;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\GameMode;

class ReachA extends Detection {
    private bool $run = false;
    
    private ?Entity $targetedEntity = null;
    
    private float $interpolationStep = 0.1;
    private float $attackOffset = 0.0;
    
    private Vector3 $startAttackPos;
    private Vector3 $endAttackPos;
    
    private Vector3 $startEntityPos;
    private Vector3 $endEntityPos;
    
    private float $closestRawDistance = 0.0;
    private array $raycastResults = [];
    
    public function __construct() {
        parent::__construct("Reach", "A");
    }
    
    public function getDescription(): string {
        return "This checks if a player's combat range is invalid.";
    }
    
    public function getMaxViolations(): float {
        return 10.0;
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
            
            $gameMode = $user->getGameMode();
            if ($gameMode !== GameMode::SURVIVAL && $gameMode !== GameMode::ADVENTURE) {
                return;
            }
            
            $entity = $user->searchEntity($trData->getActorRuntimeId());
            if ($entity === null) {
                return;
            }
            
            if ($entity->getTeleportationTicks() <= 20) {
                return;
            }
            
            if ($user->getEntity()->getTeleportationTicks() <= 20) {
                return;
            }
            
            $this->attackOffset = 1.62;
            if ($user->isSneaking()) {
                $this->attackOffset = 1.54;
            }
            
            $this->run = true;
            $this->targetedEntity = $entity;
            
            $this->startAttackPos = $user->getEntity()->getLastPosition()->add(0, $this->attackOffset, 0);
            $this->endAttackPos = $user->getEntity()->getPosition()->add(0, $this->attackOffset, 0);
            
            $this->startEntityPos = $entity->getLastPosition();
            $this->endEntityPos = $entity->getPosition();
            
            $bb1 = $this->growAABB($entity->getBox($entity->getLastRotation()), 0.1);
            $bb2 = $this->growAABB($entity->getBox($entity->getPosition()), 0.1);
            
            $point1 = $this->closestPointToBBox($this->startAttackPos, $bb1);
            $point2 = $this->closestPointToBBox($this->endAttackPos, $bb1);
            $point3 = $this->closestPointToBBox($this->startAttackPos, $bb2);
            $point4 = $this->closestPointToBBox($this->endAttackPos, $bb2);
            
            $close1 = min(
                $point1->subtract($this->startAttackPos)->length(),
                $point2->subtract($this->endAttackPos)->length()
            );
            $close2 = min(
                $point3->subtract($this->startAttackPos)->length(),
                $point4->subtract($this->endAttackPos)->length()
            );
            
            $this->closestRawDistance = min($close1, $close2);
            
            if ($this->closestRawDistance <= 3) {
                $this->violations = max($this->violations - 0.02, 0);
                return;
            }
            
            if ($this->buff(1, 10) < 5) {
                return;
            }
            
            $this->flag($user, $this->violationAfterTicks($user->getClientFrame(), 300), [
                "raw" => round($this->closestRawDistance, 2)
            ]);
            
        } elseif ($packet instanceof PlayerAuthInputPacket) {
            if (!$this->run) {
                return;
            }
            
            $this->run = false;
            
            if ($this->targetedEntity === null) {
                return;
            }
            
            if ($packet->getInputMode() !== PlayerAuthInputPacket::INPUT_MODE_MOUSE) {
                return;
            }
            
            $gameMode = $user->getGameMode();
            if ($gameMode !== GameMode::SURVIVAL && $gameMode !== GameMode::ADVENTURE) {
                return;
            }
            
            $attackPosDelta = $this->endAttackPos->subtract($this->startAttackPos);
            $entityPosDelta = $this->endEntityPos->subtract($this->startEntityPos);
            
            $startRotation = $user->getEntity()->getLastRotation();
            $endRotation = $user->getEntity()->getRotation();
            $rotationDelta = $endRotation->subtract($startRotation);
            
            if ($rotationDelta->length() >= 180) {
                return;
            }
            
            $altEntityStartPos = $this->targetedEntity->getLastPosition();
            $altEntityEndPos = $this->targetedEntity->getPosition();
            $altEntityPosDelta = $altEntityEndPos->subtract($altEntityStartPos);
            
            $this->raycastResults = [];
            
            for ($partialTicks = 0.0; $partialTicks <= 1.0; $partialTicks += $this->interpolationStep) {
                $attackPos = $this->startAttackPos->add(
                    $attackPosDelta->x * $partialTicks,
                    $attackPosDelta->y * $partialTicks,
                    $attackPosDelta->z * $partialTicks
                );
                
                $entityPos = $this->startEntityPos->add(
                    $entityPosDelta->x * $partialTicks,
                    $entityPosDelta->y * $partialTicks,
                    $entityPosDelta->z * $partialTicks
                );
                
                $bb = $this->growAABB($this->targetedEntity->getBox($entityPos), 0.1);
                
                $rotation = $startRotation->add(
                    $rotationDelta->x * $partialTicks,
                    $rotationDelta->y * $partialTicks,
                    $rotationDelta->z * $partialTicks
                );
                
                $directionVec = $this->getDirectionVector($rotation->z, $rotation->x)->multiply(14);
                
                $result = $this->bboxIntercept($bb, $attackPos, $attackPos->add($directionVec->x, $directionVec->y, $directionVec->z));
                if ($result !== null) {
                    $this->raycastResults[] = $attackPos->subtract($result)->length();
                }
                
                $entityPos = $altEntityStartPos->add(
                    $altEntityPosDelta->x * $partialTicks,
                    $altEntityPosDelta->y * $partialTicks,
                    $altEntityPosDelta->z * $partialTicks
                );
                
                $bb = $this->growAABB($this->targetedEntity->getBox($entityPos), 0.1);
                $result = $this->bboxIntercept($bb, $attackPos, $attackPos->add($directionVec->x, $directionVec->y, $directionVec->z));
                if ($result !== null) {
                    $this->raycastResults[] = $attackPos->subtract($result)->length();
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
                $this->buff(-0.01);
                if ($this->buffer <= 1) {
                    $this->violations = max($this->violations - 0.001, 0);
                }
                return;
            }
            
            if ($this->buff(1, 7) < 5) {
                return;
            }
            
            $this->flag($user, 1, [
                "min" => round($minDist, 2),
                "max" => round($maxDist, 2)
            ]);
        }
    }
    
    private function closestPointToBBox(Vector3 $point, AABB $bb): Vector3 {
        $x = max($bb->minX, min($point->x, $bb->maxX));
        $y = max($bb->minY, min($point->y, $bb->maxY));
        $z = max($bb->minZ, min($point->z, $bb->maxZ));
        
        return new Vector3($x, $y, $z);
    }
    
    private function growAABB(AABB $bb, float $amount): AABB {
        return new AABB(
            $bb->minX - $amount,
            $bb->minY - $amount,
            $bb->minZ - $amount,
            $bb->maxX + $amount,
            $bb->maxY + $amount,
            $bb->maxZ + $amount
        );
    }
    
    private function getDirectionVector(float $yaw, float $pitch): Vector3 {
        $y = -sin(deg2rad($pitch));
        $xz = cos(deg2rad($pitch));
        $x = -$xz * sin(deg2rad($yaw));
        $z = $xz * cos(deg2rad($yaw));
        
        return new Vector3($x, $y, $z);
    }
    
    private function bboxIntercept(AABB $bb, Vector3 $start, Vector3 $end): ?Vector3 {
        $ray = new Ray($start, $end->subtract($start)->normalize());
        $distance = $bb->collidesRay($ray, 0, $start->distance($end));
        
        if ($distance < 0) {
            return null;
        }
        
        return $start->add(
            $ray->direction(0) * $distance,
            $ray->direction(1) * $distance,
            $ray->direction(2) * $distance
        );
    }
}