<?php

namespace milk\pureentities\entity;

use milk\pureentities\entity\animal\Animal;
use milk\pureentities\entity\monster\walking\PigZombie;
use pocketmine\block\Liquid;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Creature;

abstract class WalkingEntity extends BaseEntity{

    protected function checkTarget(){
        if($this->isKnockback()){
            return;
        }

        $target = $this->baseTarget;
        if(!$target instanceof Creature or !$this->targetOption($target, $this->distanceSquared($target))){
            $near = PHP_INT_MAX;
            foreach ($this->getLevel()->getEntities() as $creature){
                if($creature === $this || !($creature instanceof Creature) || $creature instanceof Animal){
                    continue;
                }

                if($creature instanceof BaseEntity && $creature->isFriendly() == $this->isFriendly()){
                    continue;
                }

                $distance = $this->distanceSquared($creature);
                if(
                    $distance <= 100
                    && $this instanceof PigZombie && $this->isAngry()
                    && $creature instanceof PigZombie && !$creature->isAngry()
                ){
                    $creature->setAngry(1000);
                }

                if($distance > $near or !$this->targetOption($creature, $distance)){
                    continue;
                }
                $near = $distance;

                $this->moveTime = 0;
                $this->baseTarget = $creature;
            }
        }

        if($this->baseTarget instanceof Creature && $this->baseTarget->isAlive()){
            return;
        }

        if($this->moveTime <= 0 or !($this->baseTarget instanceof Vector3)){
            $x = mt_rand(20, 100);
            $z = mt_rand(20, 100);
            $this->moveTime = mt_rand(300, 1200);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, 0, mt_rand(0, 1) ? $z : -$z);
        }
    }

    /**
     * @param int $dx
     * @param int $dz
     *
     * @return bool
     */
    protected function checkJump($dx, $dz){
        if($this->motionY < 0){
            return false;
        }

        if($this->motionY == $this->gravity * 2){
            return $this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Liquid;
        }else if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) ($this->y + 0.8), Math::floorFloat($this->z))) instanceof Liquid){
            $this->motionY = $this->gravity * 2;
            return true;
        }

        if($this->stayTime > 0){
            return false;
        }

        $block = $this->level->getBlock($this->add($dx, 0, $dz));
        if($block instanceof Slab || $block instanceof Stair){
            $this->motionY = 0.5;
            return true;
        }
        return false;
    }

    /**
     * @param int $tickDiff
     *
     * @return null|Vector3
     */
    public function updateMove($tickDiff){
        if(!$this->isMovement()){
            return null;
        }

        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.2 * $tickDiff;
            $this->updateMovement();
            return null;
        }
        
        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = -atan2($x / $diff, $z / $diff) * 180 / M_PI;
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $dx = $this->motionX * $tickDiff;
        $dz = $this->motionZ * $tickDiff;
        $isJump = $this->checkJump($dx, $dz);
        if($this->stayTime > 0){
            $this->stayTime -= $tickDiff;
            $this->move(0, $this->motionY * $tickDiff, 0);
        }else{
            $be = new Vector2($this->x + $dx, $this->z + $dz);
            $this->move($dx, $this->motionY * $tickDiff, $dz);
            $af = new Vector2($this->x, $this->z);

            if(($be->x != $af->x || $be->y != $af->y) && !$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if(!$isJump){
            if($this->onGround){
                $this->motionY = 0;
            }elseif($this->motionY > -$this->gravity * 4){
                $this->motionY = -$this->gravity * 4;
            }else{
                $this->motionY -= $this->gravity;
            }
        }
        $this->updateMovement();
        return $this->baseTarget;
    }

}