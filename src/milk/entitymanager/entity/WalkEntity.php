<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;

abstract class WalkEntity extends BaseEntity{
    private function checkTarget(){
    	if(count($this->getViewers()) == 0)
    		return;
        $target = $this->baseTarget;
        if(!$target instanceof Creature or !$this->targetOption($target, $this->distanceSquared($target))){
            $near = PHP_INT_MAX;

            foreach ($this->getLevel()->getEntities() as $creature){
            	if(! $creature instanceof Creature) continue;
            	if($creature instanceof Animal) continue;
            	
            	if($creature === $this) continue;
            	if($creature instanceof BaseEntity)
            		if($creature->isFriendly() == $this->isFriendly()) continue;
            	
                if(($distance = $this->distanceSquared($creature)) > $near or !$this->targetOption($creature, $distance)) continue;
                $near = $distance;
                $this->baseTarget = $creature;
            }
        }
        if($this->baseTarget instanceof Creature)
        	if($this->baseTarget->isAlive())
        		return;
        if($this->stayTime > 0){
            if(mt_rand(1, 125) > 4) return;
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(-20, 20) / 10, mt_rand(0, 1) ? $z : -$z);
        }elseif(mt_rand(1, 420) == 1){
            $this->stayTime = mt_rand(95, 420);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(-20, 20) / 10, mt_rand(0, 1) ? $z : -$z);
        }elseif($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $this->moveTime = mt_rand(100, 1000);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove(){
        if(!$this->isMovement()) return null;
        /** @var Vector3 $target */
        
        if($this->attacker instanceof Entity){
            if($this->atkTime == 16){
                $target = $this->attacker;
                $x = $target->x - $this->x;
                $z = $target->z - $this->z;
                $diff = abs($x) + abs($z);
                $this->motionX = -0.5 * ($diff == 0 ? 0 : $x / $diff);
                $this->motionZ = -0.5 * ($diff == 0 ? 0 : $z / $diff);
                --$this->atkTime;
            }
            $y = [11 => 0.3, 12 => 0.3, 13 => 0.4, 14 => 0.4, 15 => 0.5, 16 => 0.5];
            $this->move($this->motionX, isset($y[$this->atkTime]) ?  $y[$this->atkTime] : -0.2, $this->motionZ);
            if(--$this->atkTime <= 0){
            	$this->attacker = null;
            	$this->motionX = 0;
            	$this->motionY = 0;
            	$this->motionZ = 0;
            }
            return null;
        }
        
        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            if($this->stayTime > 0 || $x ** 2 + $z ** 2 < 0.5){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $diff = abs($x) + abs($z);
                $this->motionX = $this->speed * 0.15 * ($x / $diff);
                $this->motionZ = $this->speed * 0.15 * ($z / $diff);
            }
            //$this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
            $this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }
        $target = $this->mainTarget != null ? $this->mainTarget : $this->baseTarget;
        if($this->stayTime > 0){
            --$this->stayTime;
        }else{
            $isJump = false;
            $dx = $this->motionX;
            $dy = $this->motionY;
            $dz = $this->motionZ;

            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $dy, $dz);
            $af = new Vector2($this->x, $this->z);

            if($be->x != $af->x || $be->y != $af->y){
                $x = 0;
                $z = 0;
                if($be->x - $af->x != 0) $x += $be->x - $af->x > 0 ? 1 : -1;
                if($be->y - $af->y != 0) $z += $be->y - $af->y > 0 ? 1 : -1;

                $block = $this->level->getBlock((new Vector3(Math::floorFloat($be->x) + $x, $this->y, Math::floorFloat($af->y) + $z))->floor());
                $block2 = $this->level->getBlock((new Vector3(Math::floorFloat($be->x) + $x, $this->y + 1, Math::floorFloat($af->y) + $z))->floor());
                if(!$block->canPassThrough()){
                    $bb = $block2->getBoundingBox();
                    if($block2->canPassThrough() || ($bb == null || ($bb != null && $bb->maxY - $this->y <= 1))){
                        $isJump = true;
                        $this->motionY = 0.2;
                    }else{
                        if($this->level->getBlock($block->add(-$x, 0, -$z))->getId() == Item::LADDER){
                            $isJump = true;
                            $this->motionY = 0.2;
                        }
                    }
                }
                if(!$isJump){
                    $this->moveTime = 0;
                }
            }

            if($this->onGround && !$isJump){
                $this->motionY = 0;
            }elseif(!$isJump){
                $this->motionY -= $this->gravity;
            }
        }
        $this->updateMovement();
        return $target;
    }

}