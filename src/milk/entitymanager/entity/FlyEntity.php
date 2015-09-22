<?php

namespace milk\entitymanager\entity;

use pocketmine\math\Vector3;
use pocketmine\Player;

abstract class FlyEntity extends BaseEntity{
    //TODO: This isn't implemented yet

    private function checkTarget(){
        /*$get = function(Vector3 $pos, Vector3 $pos1){
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
        if($this->baseTarget instanceof Player) return;*/

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
            $y = mt_rand(10, 40);
            $z = mt_rand(25, 80);
            $this->baseTarget = $this->add(mt_rand(0, 1) ? $x : -$x, mt_rand(0, 1) ? $y : -$y, mt_rand(0, 1) ? $z : -$z);
        }
    }

    public function updateMove(){
        if(!$this->isMovement()) return null;
        /** @var Vector3 $target */
        if($this->attacker instanceof Player && $this->attackTime > 0){
            if($this->attackTime == 5 || ($this->motionX === 0 && $this->motionZ === 0)){
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
            if(--$this->attackTime <= 0) $this->attacker = null;
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
                $this->motionY = $this->speed * 0.04 * ($y / abs($x) + abs($y));
                $this->motionZ = $this->speed * 0.15 * ($z / $diff);
            }
            $f = sqrt(($this->motionX ** 2) + ($this->motionZ ** 2));
            $this->yaw = (-atan2($this->motionX, $this->motionZ) * 180 / M_PI);
            $this->pitch = (-atan2($f, $this->motionY) * 180 / M_PI);
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