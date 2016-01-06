<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Rideable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Pig extends Animal implements Rideable{
    const NETWORK_ID = 12;

    public $width = 1.6;
    public $length = 0.8;
    public $height = 1.12;

    public function getName(){
        return "Pig";
    }

    public function initEntity(){
        $this->setMaxHealth(10);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        parent::initEntity();
        $this->created = true;
    }

    public function targetOption(Creature $creature, $distance){
    	if($creature instanceof Player)
        	return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::CARROT && $distance <= 49;
    	return false;
    }

    public function getDrops(){
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [Item::get(Item::RAW_PORKCHOP, 0, 1)];
        }
        return [];
    }

}