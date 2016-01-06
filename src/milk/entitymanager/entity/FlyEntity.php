<?php

namespace milk\entitymanager\entity;

use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\entity\Creature;

abstract class FlyEntity extends BaseEntity{
    private function checkTarget(){
        $get = function(Vector3 $pos, Vector3 $pos1){
            return ($pos1->x - $pos->x) ** 2 + ($pos1->y - $pos->y) ** 2 + ($pos1->z - $pos->z) ** 2;
        };
    	if(count($this->getViewers()) == 0)
    		return;
        $target = $this->baseTarget;
        if(!$target instanceof Creature or !$this->targetOption($target, $get($this, $target))){
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
            $ground = $this->getLevel()->getHighestBlockAt($this->x, $this->z);
            $maxAltitude = $ground + 10;
            if($this->y > $maxAltitude){
            	$y = mt_rand(-10, -7);
            }else{
            	$y = mt_rand(-2, 2);
            }
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }elseif(mt_rand(1, 420) == 1){
            $this->stayTime = mt_rand(95, 420);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $ground = $this->getLevel()->getHighestBlockAt($this->x, $this->z);
            $maxAltitude = $ground + 10;
            if($this->y > $maxAltitude){
            	$y = mt_rand(-10, -7);
            }else{
            	$y = mt_rand(-2, 2);
            }
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }elseif($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $this->moveTime = mt_rand(100, 1000);
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            $ground = $this->getLevel()->getHighestBlockAt($this->x, $this->z);
            $maxAltitude = $ground + 10;
            if($this->y > $maxAltitude){
            	$y = mt_rand(-10, -7);
            }else{
            	$y = mt_rand(-2, 2);
            }
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove(){
        if(!$this->isMovement()) return null;
        /** @var Vector3 $target */
        if($this->attacker instanceof Player && $this->atkTime > 0){
            if($this->atkTime == 5 || ($this->motionX === 0 && $this->motionZ === 0)){
                $target = $this->attacker;
                $x = $target->x - $this->x;
                $y = $this->baseTarget->y - $this->y;
                $z = $target->z - $this->z;
                $diff = abs($x) + abs($z);
                $this->motionX = -0.5 * ($diff == 0 ? 0 : $x / $diff);
                $this->motionY = $this->speed * 0.1 * (($k = abs($x) + abs($y)) == 0 ? 0 : $y / $k);
                $this->motionZ = -0.5 * ($diff == 0 ? 0 : $z / $diff);
            }
            $this->move($this->motionX, $this->motionY, $this->motionZ);
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
        if($this->baseTarget instanceof Player or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            if($this->stayTime > 0 || $x ** 2 + $z ** 2 < 0.5){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $diff = abs($x) + abs($z);
                $this->motionX = $this->speed * 0.15 * ($x / $diff);
                $this->motionY = $this->speed * 0.15 * ($y / $diff);
                $this->motionZ = $this->speed * 0.15 * ($z / $diff);
            }
            $f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
            $this->yaw = (-atan2($this->motionX, $this->motionZ) * 180 / M_PI);
            //$this->pitch = (-atan2($f, $this->motionY) * 180 / M_PI);
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }
        $target = $this->mainTarget != null ? $this->mainTarget : $this->baseTarget;
        if($this->stayTime > 0){
            --$this->stayTime;
        }else{
            $dx = $this->motionX;
            $dy = $this->motionY;
            $dz = $this->motionZ;

            $this->move($dx, $dy, $dz);
        }
        $this->updateMovement();
        return $target;
    }

}