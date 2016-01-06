<?php

namespace milk\entitymanager\entity;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Mooshroom extends Animal{
    const NETWORK_ID = 16;

    public $width = 1.6;
    public $height = 1.12;

    public function getName(){
        return "Mooshroom";
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
        	return $creature->spawned && $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
        return false;
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
              $drops[] = Item::get(Item::MUSHROOM_STEW, 0, 1);
        }
        return $drops;
    }
}