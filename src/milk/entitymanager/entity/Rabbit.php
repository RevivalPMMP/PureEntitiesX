<?php

namespace milk\entitymanager\entity;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\Creature;

class Rabbit extends Animal{
    const NETWORK_ID = 18;

    public $width = 0.4;
    public $height = 0.75;

    protected $speed = 1.2;
    
    public function getName(){
        return "Rabbit";
    }

    public function initEntity(){
        $this->setMaxHealth(4);
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
        	return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::SEEDS && $distance <= 49;
        return false;
    }

    public function getDrops(){
    	return [];
    }

}