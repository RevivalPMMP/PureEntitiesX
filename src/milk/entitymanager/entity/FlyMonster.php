<?php

namespace milk\entitymanager\entity;

use pocketmine\block\Water;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Timings;
use pocketmine\math\Math;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Creature;

abstract class FlyMonster extends FlyEntity{

    private $minDamage = [0, 0, 0, 0];
    private $maxDamage = [0, 0, 0, 0];

    private $entityTick = 0;

    protected $attackDelay = 0;

    public abstract function attackEntity(Entity $player);

    public function getDamage($difficulty = null) : float{
        return mt_rand($this->getMinDamage($difficulty), $this->getMaxDamage($difficulty));
    }

    public function getMinDamage($difficulty = null) : float{
        if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $difficulty = (int) $difficulty;
        return $this->minDamage[$difficulty];
    }

    public function getMaxDamage($difficulty = null) : float{
        if($difficulty === null or !is_numeric($difficulty) || $difficulty > 3 || $difficulty < 0){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        $difficulty = (int) $difficulty;
        return $this->maxDamage[$difficulty];
    }

    /**
     * @param float|float[] $damage
     * @param int $difficulty
     */
    public function setDamage($damage, $difficulty = null){
        $this->setMinDamage($damage, $difficulty);
        $this->setMaxDamage($damage, $difficulty);
    }

    public function setMinDamage($damage, $difficulty = null){
        $difficulty = $difficulty === null ? Server::getInstance()->getDifficulty() : (int) $difficulty;
        if(is_array($damage)){
            foreach($damage as $key => $float){
                if(!is_numeric($key) || !is_numeric($float) || $key > 3 || $key < 0) continue;
                $key = (int) $key;
                $float = (float) $float;
                if($this->maxDamage[$key] >= $float) $this->minDamage[$key] = $float;
            }
        }elseif($difficulty >= 1 && $difficulty <= 3){
            $damage = (float) $damage;
            if($this->maxDamage[$difficulty] >= $damage) $this->minDamage[$difficulty] = $damage;
        }
    }

    public function setMaxDamage($damage, $difficulty = null){
        $difficulty = $difficulty === null ? Server::getInstance()->getDifficulty() : (int) $difficulty;
        if(is_array($damage)){
            foreach($damage as $key => $float){
                if(!is_numeric($key) || !is_numeric($float) || $key > 3 || $key < 0) continue;
                $key = (int) $key;
                $float = (float) $float;
                if($this->minDamage[$key] <= $float) $this->maxDamage[$key] = $float;
            }
        }elseif($difficulty >= 1 && $difficulty <= 3){
            $damage = (float) $damage;
            if($this->minDamage[$difficulty] <= $damage) $this->maxDamage[$difficulty] = $damage;
        }
    }

    public function updateTick(){
        if($this->server->getDifficulty() < 1){
            $this->close();
            return;
        }
        if(!$this->isAlive()){
            if(++$this->deadTicks >= 23) $this->close();
            return;
        }

        --$this->moveTime;
        ++$this->attackDelay;
        $target = $this->updateMove();
        if($target instanceof Entity){
            $this->attackEntity($target);
        }elseif($target instanceof Vector3){
            if((($this->x - $target->x) ** 2 + ($this->z - $target->z) ** 2) <= 1) $this->moveTime = 0;
        }
        if($this->entityTick++ >= 5){
            $this->entityTick = 0;
            $this->entityBaseTick(5);
        }
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        if(!$this->isCreated()){
            return false;
        }

        $hasUpdate = Entity::entityBaseTick($tickDiff);
        if($this->atkTime > 0){
            $this->atkTime -= $tickDiff;
        }
        if($this->isInsideOfSolid()){
            $hasUpdate = true;
            $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 1);
            $this->attack($ev->getFinalDamage(), $ev);
        }
        if($this instanceof Enderman){
            if($this->level->getBlock(new Vector3(Math::floorFloat($this->x), (int) $this->y, Math::floorFloat($this->z))) instanceof Water){
                $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                $this->attack($ev->getFinalDamage(), $ev);
                $this->move(mt_rand(-20, 20), mt_rand(-20, 20), mt_rand(-20, 20));
            }
        }else{
            if(!$this->hasEffect(Effect::WATER_BREATHING) && $this->isInsideOfWater()){
                $hasUpdate = true;
                $airTicks = $this->getDataProperty(self::DATA_AIR) - $tickDiff;
                if($airTicks <= -20){
                    $airTicks = 0;
                    $ev = new EntityDamageEvent($this, EntityDamageEvent::CAUSE_DROWNING, 2);
                    $this->attack($ev->getFinalDamage(), $ev);
                }
                $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, $airTicks);
            }else{
                $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 300);
            }
        }

        Timings::$timerEntityBaseTick->stopTiming();
        return $hasUpdate;
    }

    public function targetOption(Creature $creature, $distance){
        if($creature instanceof Player)
        	return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->isSurvival() && $distance <= 200;
        return $creature->isAlive() && !$creature->closed && $distance <= 200;
    }

}