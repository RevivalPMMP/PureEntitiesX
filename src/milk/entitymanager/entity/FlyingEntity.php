<?php

namespace milk\entitymanager\entity;

use milk\entitymanager\entity\animal\Animal;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\entity\Creature;

abstract class FlyingEntity extends BaseEntity{

    private function checkTarget(){
        if($this->isKnockback()){
            return;
        }

        $target = $this->baseTarget;
        if(!($target instanceof Creature) or !$this->targetOption($target, $this->distanceSquared($target))){
            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
                    continue;
                }

                if(
                    $creature instanceof BaseEntity
                    && $creature->isFriendly() == $this->isFriendly()
                ){
                    continue;
                }

                if(($distance = $this->distanceSquared($creature)) > $near or !$this->targetOption($creature, $distance)){
                    continue;
                }

                $near = $distance;
                $this->baseTarget = $creature;
            }
        }

        if(
            $this->baseTarget instanceof Creature
            && $this->baseTarget->isAlive()
        ){
            return;
        }

        if($this->stayTime > 0){
            if(mt_rand(1, 110) > 5){
                return;
            }

            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            if($this->y > $this->getLevel()->getHighestBlockAt($this->x, $this->z) + 10){
                $y = mt_rand(-10, -7);
            }else{
                $y = mt_rand(-10, 10);
            }
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }elseif(mt_rand(1, 370) == 1){
            $x = mt_rand(25, 80);
            $z = mt_rand(25, 80);
            if($this->y > $this->getLevel()->getHighestBlockAt($this->x, $this->z) + 10){
                $y = mt_rand(-10, -7);
            }else{
                $y = mt_rand(-10, 10);
            }
            $this->stayTime = mt_rand(90, 400);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }elseif($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $x = mt_rand(30, 100);
            $z = mt_rand(30, 100);
            if($this->y > $this->getLevel()->getHighestBlockAt($this->x, $this->z) + 10){
                $y = mt_rand(-10, -7);
            }else{
                $y = mt_rand(-2, 2);
            }
            $this->stayTime = 0;
            $this->moveTime = mt_rand(300, 1200);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove(int $tickDiff) : Vector3{
        if(!$this->isMovement()){
            return null;
        }

        /** @var Vector3 $target */
        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY * $tickDiff, $this->motionZ * $tickDiff);
            $this->updateMovement();
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
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionY = $this->getSpeed() * 0.27 * ($y / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $target = $this->mainTarget != null ? $this->mainTarget : $this->baseTarget;
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
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