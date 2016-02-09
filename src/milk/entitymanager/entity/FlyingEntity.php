<?php

namespace milk\entitymanager\entity;

use milk\entitymanager\entity\animal\Animal;
use milk\entitymanager\entity\monster\flying\Blaze;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
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

        $maxY = max($this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z) + 15, 120);
        if($this->moveTime <= 0 or !$this->baseTarget instanceof Vector3){
            $x = mt_rand(20, 100);
            $z = mt_rand(20, 100);
            if($this->y > $maxY){
                $y = mt_rand(-12, -4);
            }else{
                $y = mt_rand(-10, 10);
            }
            $this->moveTime = mt_rand(300, 1200);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, $y, mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove(int $tickDiff){
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
            if($x ** 2 + $z ** 2 < 0.5){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $diff = abs($x) + abs($z);
                if($this instanceof Blaze){
                    if($this->baseTarget instanceof Creature){
                        $this->motionX = 0;
                        $this->motionZ = 0;
                        if($this->distance($this->baseTarget) < $this->y - $this->getLevel()->getHighestBlockAt((int) $this->x, (int) $this->z)){
                            $this->motionY = $this->gravity * 4;
                        }else{
                            $this->motionY = 0;
                        }
                    }else{
                        $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                        $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
                    }
                }else{
                    $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                    $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
                    $this->motionY = $this->getSpeed() * 0.27 * ($y / $diff);
                }
            }
            $this->yaw = -atan2($this->motionX, $this->motionZ) * 180 / M_PI;
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x ** 2 + $z ** 2)));
        }

        $target = $this->baseTarget;
        $isJump = false;
        $dx = $this->motionX * $tickDiff;
        $dy = $this->motionY * $tickDiff;
        $dz = $this->motionZ * $tickDiff;

        $be = new Vector2($this->x + $dx, $this->z + $dz);
        $this->move($dx, $dy, $dz);
        $af = new Vector2($this->x, $this->z);

        if($be->x != $af->x || $be->y != $af->y){
            if($this instanceof Blaze){
                $x = 0;
                $z = 0;
                if($be->x - $af->x != 0){
                    $x = $be->x > $af->x ? 1 : -1;
                }
                if($be->y - $af->y != 0){
                    $z = $be->y > $af->y ? 1 : -1;
                }

                $vec = new Vector3(Math::floorFloat($be->x) + $x, $this->y, Math::floorFloat($be->y) + $z);
                $block = $this->level->getBlock($vec->add($x, 0, $z));
                $block2 = $this->level->getBlock($vec->add($x, 1, $z));
                if(!$block->canPassThrough()){
                    $bb = $block2->getBoundingBox();
                    if(
                        $this->motionY > -$this->gravity * 4
                        && ($block2->canPassThrough() || ($bb == null || $bb->maxY - $this->y <= 1))
                    ){
                        $isJump = true;
                        if($this->motionY >= 0.3){
                            $this->motionY += $this->gravity;
                        }else{
                            $this->motionY = 0.3;
                        }
                    }
                }

                if(!$isJump){
                    $this->moveTime -= 90 * $tickDiff;
                }
            }else{
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if($this instanceof Blaze){
            if($this->onGround && !$isJump){
                $this->motionY = 0;
            }else if(!$isJump){
                if($this->motionY > -$this->gravity * 4){
                    $this->motionY = -$this->gravity * 4;
                }else{
                    $this->motionY -= $this->gravity;
                }
            }
        }
        $this->updateMovement();
        return $target;
    }

}