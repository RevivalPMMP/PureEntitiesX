<?php

namespace milk\entitymanager\entity\monster\walking;

use milk\entitymanager\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Math;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Spider extends WalkingMonster{
    const NETWORK_ID = 35;

    public $width = 1.3;
    public $height = 1.12;

    public function getSpeed() : float{
        return 1.13;
    }

    public function initEntity(){
        parent::initEntity();

        $this->setMaxHealth(16);
        $this->setDamage([0, 2, 2, 3]);
    }

    public function getName() : string{
        return "Spider";
    }

    public function onUpdate($currentTick){
        if($this->server->getDifficulty() < 1){
            $this->close();
            return false;
        }

        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23){
                $this->close();
                return false;
            }
            return true;
        }

        $tickDiff = $currentTick - $this->lastUpdate;
        $this->lastUpdate = $currentTick;
        $this->entityBaseTick($tickDiff);

        if(!$this->isMovement()){
            return null;
        }

        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.15 * $tickDiff;
            $this->updateMovement();
            return null;
        }

        $target = $this->updateMove($tickDiff);
        if($this->isFriendly()){
            if(!($target instanceof Player)){
                if($target instanceof Entity){
                    $this->attackEntity($target);
                }elseif(
                    $target instanceof Vector3
                    &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
                ){
                    $this->moveTime = 0;
                }
            }
        }else{
            if($target instanceof Entity){
                $this->attackEntity($target);
            }elseif(
                $target instanceof Vector3
                &&(($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1
            ){
                $this->moveTime = 0;
            }
        }
        return true;
    }

    public function updateMove(int $tickDiff){
        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;
            if($x ** 2 + $z ** 2 < 0.7){
                $this->motionX = 0;
                $this->motionZ = 0;
            }else{
                $diff = abs($x) + abs($z);
                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
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
            $x = 0;
            $z = 0;
            if($be->x - $af->x != 0){
                $x += $be->x - $af->x > 0 ? 1 : -1;
            }
            if($be->y - $af->y != 0){
                $z += $be->y - $af->y > 0 ? 1 : -1;
            }

            $vec = new Vector3(Math::floorFloat($be->x), (int) $this->y, Math::floorFloat($be->y));
            $block = $this->level->getBlock($vec->add($x, 0, $z));
            $block2 = $this->level->getBlock($vec->add($x, 1, $z));
            if(!$block->canPassThrough()){
                $bb = $block2->getBoundingBox();
                if(
                    $this->motionY > -$this->gravity * 4
                    && ($block2->canPassThrough() || ($bb == null || ($bb != null && $bb->maxY - $this->y <= 1)))
                ){
                    $isJump = true;
                    if($this->motionY >= 0.3){
                        $this->motionY += $this->gravity;
                    }else{
                        $this->motionY = 0.3;
                    }
                }elseif($this->level->getBlock($vec)->getId() == Item::LADDER){
                    $isJump = true;
                    $this->motionY = 0.15;
                }
            }

            if(!$isJump){
                $this->moveTime -= 90 * $tickDiff;
            }
        }

        if($this->onGround && !$isJump){
            $this->motionY = 0;
        }elseif(!$isJump){
            if($this->motionY > -$this->gravity * 4){
                $this->motionY = -$this->gravity * 4;
            }else{
                $this->motionY -= $this->gravity;
            }
        }
        $this->updateMovement();
        return $target;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, mt_rand(0, 3))] : [];
    }

}
