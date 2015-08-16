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

abstract class Monster extends WalkEntity{

    private $damage = [];

    protected $attackDelay = 0;

    public abstract function attackEntity(Entity $player);

    /**
     * @param int $difficulty
     *
     * @return int
     */
    public function getDamage($difficulty = null){
        if($difficulty === null or !is_numeric($difficulty)){
            $difficulty = Server::getInstance()->getDifficulty();
        }
        return isset($this->damage[(int) $difficulty]) ? $this->damage[(int) $difficulty] : 0;
    }

    /**
     * @param float|float[] $damage
     * @param int $difficulty
     */
    public function setDamage($damage, $difficulty = null){
        $difficulty = $difficulty === null ? Server::getInstance()->getDifficulty() : (int) $difficulty;
        if(is_array($damage)){
            foreach($damage as $key => $int){
                if(!is_numeric($key) || $key > 3 || $key < 0) continue;
                $this->damage[(int) $key] = (float) $int;
            }
        }elseif($difficulty >= 1 && $difficulty <= 3){
            $this->damage[$difficulty] = (float) $damage;
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
        $this->entityBaseTick();
    }

    public function entityBaseTick($tickDiff = 1){
        Timings::$timerEntityBaseTick->startTiming();

        if(!$this->isCreated()){
            return false;
        }

        $hasUpdate = Entity::entityBaseTick($tickDiff);
        if($this->attackTime > 0){
            $this->attackTime -= $tickDiff;
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

    public function targetOption(Player $player, $distance){
        return $player->spawned && $player->isAlive() && !$player->closed && $player->isSurvival() && $distance <= 81;
    }

}