<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Wolf extends Monster{
    const NETWORK_ID = 14;

    public $width = 0.72;
    public $length = 0.6;
    public $height = 0.9;

    private $angry = 0;

    public function getSpeed() : float{
        return 1.2;
    }

    public function initEntity(){
        $this->setMaxHealth(8);

        parent::initEntity();
        $this->fireProof = true;

        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }

        $this->setDamage([0, 3, 4, 6]);
        $this->created = true;
    }

    public function saveNBT(){
        parent::saveNBT();
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
    }

    public function getName() : string{
        return "Wolf";
    }

    public function isAngry(){
        return $this->angry > 0;
    }

    public function setAngry($val){
        $this->angry = (int) $val;
    }

    public function targetOption(Creature $creature, $distance){
        return parent::targetOption($creature, $distance) and $this->isAngry();
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
