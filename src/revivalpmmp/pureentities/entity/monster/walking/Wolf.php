<?php

namespace revivalpmmp\pureentities\entity\monster\walking;

use revivalpmmp\pureentities\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;
use revivalpmmp\pureentities\data\Data;

class Wolf extends WalkingMonster{
    const NETWORK_ID = Data::WOLF;

    private $angry = 0;

    public $width = 0.72;
    public $height = 0.9;

    public function getSpeed() : float{
        return 1.2;
    }

    public function initEntity(){
        parent::initEntity();

        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }


        $this->setMaxHealth(20);

        $this->fireProof = false;
        $this->setDamage([0, 3, 4, 6]);
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName(){
        return "Wolf";
    }

    public function isAngry() : bool{
        return $this->angry > 0;
    }

    public function setAngry(int $val){
        $this->angry = $val;
    }

    public function attack($damage, EntityDamageEvent $source){
        parent::attack($damage, $source);

        if(!$source->isCancelled()){
            $this->setAngry(1000);
        }
    }

    public function targetOption(Creature $creature, float $distance) : bool{
        return $this->isAngry() && parent::targetOption($creature, $distance);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.6){
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return [];
    }

}
