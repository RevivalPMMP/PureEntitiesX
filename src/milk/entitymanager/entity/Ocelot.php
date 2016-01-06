<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\item\Item;
use pocketmine\entity\Creature;

class Ocelot extends Monster{
    const NETWORK_ID = 22;

    public $width = 0.72;
    public $length = 0.6;
    public $height = 0.9;

    private $angry = 0;

    protected $speed = 1.5;

    public function initEntity(){
        $this->fireProof = true;
        $this->setMaxHealth(10);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        if(isset($this->namedtag->Angry)){
            $this->angry = (int) $this->namedtag["Angry"];
        }
        $this->setMinDamage([0, 2]);
        $this->setMaxDamage([0, 2]);
        parent::initEntity();
        $this->created = true;
    }

    public function saveNBT(){
        $this->namedtag->Angry = new IntTag("Angry", $this->angry);
        parent::saveNBT();
    }

    public function getName(){
        return "Ocelot";
    }

    public function isAngry(){
        return $this->angry > 0;
    }

    public function setAngry($val){
        $this->angry = (int) $val;
    }

    public function targetOption(Creature $creature, $distance){
    	if($creature instanceof Player)
    		return ($creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::RAW_FISH && $distance <= 49) or $this->isAngry();
    	return parent::targetOption($creature, $distance);
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.44){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
    	return [];
    }

}
