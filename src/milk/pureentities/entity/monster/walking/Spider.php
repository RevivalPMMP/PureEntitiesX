<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector2;
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

    public function getName(){
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

        $before = $this->baseTarget;
        $this->checkTarget();
        if($this->baseTarget instanceof Creature or $before !== $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            $distance = $this->distance($target = $this->baseTarget);
            if($distance <= 2){
                if($target instanceof Creature){
                    if($distance <= $this->width / 2 + 0.05){
                        if($this->attackDelay < 10){
                            $this->motionX = $this->getSpeed() * 0.1 * ($x / $diff);
                            $this->motionZ = $this->getSpeed() * 0.1 * ($z / $diff);
                        }else{
                            $this->motionX = 0;
                            $this->motionZ = 0;
                            $this->attackEntity($target);
                        }
                    }else{
                        if(!$this->isFriendly()){
                            $this->motionY = 0.2;
                        }
                        $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                        $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
                    }
                }else if($target != null && (pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2)) <= 1){
                    $this->moveTime = 0;
                }
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
        return true;
    }

    public function updateMove($tickDiff){
        return null;
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && (($this->isFriendly() && !($player instanceof Player)) || !$this->isFriendly())){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, mt_rand(0, 3))] : [];
    }

}
