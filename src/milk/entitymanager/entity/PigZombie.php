<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;

class PigZombie extends Monster{
    const NETWORK_ID = 36;

    public $width = 0.72;
    public $length = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    private $angry = 0;

    protected $speed = 1.15;

    public function initEntity(){
        $this->fireProof = true;
        $this->setMaxHealth(22);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }
        $this->setMinDamage([0, 5, 9, 13]);
        $this->setMaxDamage([0, 5, 9, 13]);
        parent::initEntity();
        $this->created = true;
    }

    public function saveNBT(){
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
        parent::saveNBT();
    }

    public function getName(){
        return "PigZombie";
    }

    public function isAngry(){
        return $this->angry > 0;
    }

    public function setAngry($val){
        $this->angry = (int) $val;
    }

    public function targetOption(Creature $creature, $distance){
        return parent::targetOption($creature, $distance) && $this->isAngry();
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::FLINT, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::GUNPOWDER, 0, 1);
                    break;
                case 2:
                    $drops[] = Item::get(Item::REDSTONE_DUST, 0, 1);
                    break;
            }
        }
        return $drops;
    }

}
