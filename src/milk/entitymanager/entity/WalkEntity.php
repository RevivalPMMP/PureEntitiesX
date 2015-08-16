<?php

namespace milk\entitymanager\entity;

use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class WalkEntity extends BaseEntity{

    private function updateTarget(){
        $get = function(Vector3 $pos, Vector3 $pos1){
            return ($pos1->x - $pos->x) ** 2 + ($pos1->y - $pos->y) ** 2 + ($pos1->z - $pos->z) ** 2;
        };
        $target = $this->baseTarget;
        if(!$target instanceof Player or !$this->targetOption($target, $get($this, $target))){
            $near = PHP_INT_MAX;
            foreach($this->getViewers() as $player){
                if(($distance = $this->distanceSquared($player)) > $near or !$this->targetOption($player, $distance)) continue;
                $near = $distance;
                $this->baseTarget = $player;
            }
        }
        if($this->baseTarget instanceof Player) return;

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
        if($this->attacker instanceof Player){
            if($this->attackTime == 5 || ($this->motionX === 0 && $this->motionZ === 0)){
                $target = $this->attacker;
                $x = $target->x - $this->x;
                $z = $target->z - $this->z;
                $diff = abs($x) + abs($z);
                $this->motionX = -0.5 * ($diff == 0 ? 0 : $x / $diff);
                $this->motionZ = -0.5 * ($diff == 0 ? 0 : $z / $diff);
            }
            $y = [4 => 0.32, 5 => 0.95];
            $this->move($this->motionX, isset($y[$this->attackTime]) ?  $y[$this->attackTime] : -0.32, $this->motionZ);
            if(--$this->attackTime <= 0) $this->attacker = null;
            return null;
        }
        $before = $this->baseTarget;
        $this->updateTarget();
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
                $this->motionZ = $this->speed * 0.15 * ($z / $diff);
            }
            $this->yaw = rad2deg(atan2($z, $x) - M_PI_2);
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

            $bb = $this->boundingBox;
            $maxX = Math::ceilFloat($bb->maxX + $dx);
            $maxY = Math::ceilFloat($bb->maxY + $dy);
            $maxZ = Math::ceilFloat($bb->maxZ + $dz);
            $minX = Math::floorFloat($bb->minX + $dx);
            $minY = Math::floorFloat($bb->minY + $dy);
            $minZ = Math::floorFloat($bb->minZ + $dz);

            $v = new Position(0, 0, 0, $this->level);
            for($v->x = $minX; $v->x <= $maxX; ++$v->x){
                for($v->z = $minZ; $v->z <= $maxZ; ++$v->z){
                    $chunk = $this->level->getChunk($v->x >> 4, $v->z >> 4, true);
                    if(!$chunk->isLoaded()) $chunk->load();
                    if(!$chunk->isGenerated()) $chunk->setGenerated();
                    if(!$chunk->isPopulated()) $chunk->setPopulated();
                    for($v->y = $minY; $v->y <= $maxY; ++$v->y){
                        if($v->y < 0) continue;
                        $t = Block::get($chunk->getBlockId($v->x & 0x0f, $v->y, $v->z & 0x0f), $chunk->getBlockData($v->x & 0x0f, $v->y, $v->z & 0x0f), $v);
                        if(
                            $dy == 0
                            && !$t->canPassThrough()
                            && $t->getBoundingBox() != null
                            && $this->boundingBox->minY < $bb->maxY
                            && $this->boundingBox->minY >= $bb->minY
                            && (($this->x - $t->x) ** 2 + ($this->z - $t->z) ** 2) <= 2
                        ){
                            $up = $t->getSide(Vector3::SIDE_UP)->getBoundingBox();
                            if($up == null && ($height = $bb->maxY - $this->boundingBox->minY) > 0){
                                if($height <= 0.5){
                                    $dy = 0.5;
                                    $isJump = true;
                                }elseif($height <= 1){
                                    $dy = 1;
                                    $isJump = true;
                                }
                            }elseif($up->maxY - $this->boundingBox->minY <= 1){
                                $dy = 1;
                                $isJump = true;
                            }
                        }
                    }
                }
            }
            $this->move($dx, $dy, $dz);
            if($this->onGround){
                $this->motionY = 0;
            }elseif(!$isJump && $this->motionY == 0){
                $this->motionY = -0.32;
            }
        }
        $this->updateMovement();
        return $target;
    }

}