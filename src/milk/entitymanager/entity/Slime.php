<?php

namespace milk\entitymanager\entity;

use pocketmine\item\Item;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Creature;

class Slime extends Animal{
    const NETWORK_ID = 37;

    public $width = 1.2;
    public $height = 1.2;
    
    protected $speed = 0.8;

    public function getName(){
        return "Slime";
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
    	return false;
    }
    
    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
        	$drops[] = Item::get(Item::SLIMEBALL, 0, mt_rand(0, 2));
        }
        return $drops;
    }

}