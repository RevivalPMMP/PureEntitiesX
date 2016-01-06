<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Colorable;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Sheep extends Animal implements Colorable{
    const NETWORK_ID = 13;

    public $width = 1.6;
    public $length = 0.8;
    public $height = 1.12;

    public function getName(){
        return "Sheep";
    }

    public function initEntity(){
        $this->setMaxHealth(8);
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
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            return [
                Item::get(Item::WOOL, mt_rand(0, 15), 1)
            ];
        }
        return [];
    }

}