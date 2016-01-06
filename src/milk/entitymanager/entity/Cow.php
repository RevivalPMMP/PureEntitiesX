<?php

namespace milk\entitymanager\entity;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Cow extends Animal{
    const NETWORK_ID = 11;

    public $width = 1.6;
    public $height = 1.12;

    public function getName(){
        return "Cow";
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
        	return $creature->isAlive() && !$creature->closed && $creature->getInventory()->getItemInHand()->getId() == Item::WHEAT && $distance <= 49;
    	return false;
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            switch(mt_rand(0, 1)){
                case 0 :
                    $drops[] = Item::get(Item::RAW_BEEF, 0, 1);
                    break;
                case 1 :
                    $drops[] = Item::get(Item::LEATHER, 0, 1);
                    break;
            }
        }
        return $drops;
    }
}