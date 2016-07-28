<?php

namespace milk\pureentities\entity\monster\walking;

use milk\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Explosive;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\level\Explosion;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;

class Creeper extends WalkingMonster implements Explosive{
    const NETWORK_ID = 33;
    const DATA_POWERED = 19;

    public $width = 0.72;
    public $height = 1.8;

    private $bombTime = 0;

    public function getSpeed() : float{
        return 0.9;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->IsPowered)){
            $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $this->namedtag->IsPowered ? 1 : 0);
        }elseif(isset($this->namedtag->powered)){
            $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $this->namedtag->powered ? 1 : 0);
        }

        if(isset($this->namedtag->BombTime)){
            $this->bombTime = (int) $this->namedtag["BombTime"];
        }
    }

    public function isPowered(){
        return $this->getDataProperty(self::DATA_POWERED) == 1;
    }

    public function setPowered($value = true){
        $this->namedtag->powered = $value;
        $this->setDataProperty(self::DATA_POWERED, self::DATA_TYPE_BYTE, $value ? 1 : 0);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->BombTime = new IntTag("BombTime", $this->bombTime);
    }

    public function getName(){
        return "Creeper";
    }

    public function explode(){
        $this->server->getPluginManager()->callEvent($ev = new ExplosionPrimeEvent($this, 2.8));

        if(!$ev->isCancelled()){
            $explosion = new Explosion($this, $ev->getForce(), $this);
            if($ev->isBlockBreaking()){
                $explosion->explodeA();
            }
            $explosion->explodeB();
            $this->close();
        }
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
            return true;
        }

        if($this->isKnockback()){
            $this->move($this->motionX * $tickDiff, $this->motionY, $this->motionZ * $tickDiff);
            $this->motionY -= 0.15 * $tickDiff;
            $this->updateMovement();
            return true;
        }

        $before = $this->baseTarget;
        $this->checkTarget();

        if($this->baseTarget instanceof Creature || $before != $this->baseTarget){
            $x = $this->baseTarget->x - $this->x;
            $y = $this->baseTarget->y - $this->y;
            $z = $this->baseTarget->z - $this->z;

            $diff = abs($x) + abs($z);
            $target = $this->baseTarget;
            $distance = sqrt(pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2));
            if($distance <= 4.5){
                if($target instanceof Creature){
                    $this->bombTime += $tickDiff;
                    if($this->bombTime >= 64){
                        $this->explode();
                        return false;
                    }
                }else if(pow($this->x - $target->x, 2) + pow($this->z - $target->z, 2) <= 1){
                    $this->moveTime = 0;
                }
            }else{
                $this->bombTime -= $tickDiff;
                if($this->bombTime < 0){
                    $this->bombTime = 0;
                }

                $this->motionX = $this->getSpeed() * 0.15 * ($x / $diff);
                $this->motionZ = $this->getSpeed() * 0.15 * ($z / $diff);
            }
            $this->yaw = rad2deg(-atan2($x / $diff, $z / $diff));
            $this->pitch = $y == 0 ? 0 : rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
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

    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    return [Item::get(Item::FLINT, 0, 1)];
                case 1:
                    return [Item::get(Item::GUNPOWDER, 0, 1)];
                case 2:
                    return [Item::get(Item::REDSTONE_DUST, 0, 1)];
            }
        }
        return [];
    }

}
