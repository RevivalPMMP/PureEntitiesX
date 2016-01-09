<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class Silverfish extends Monster{
    const NETWORK_ID = 39;

    public $width = 0.7;
    public $height = 0.7;

    protected $speed = 1.4;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth(8);
        }
        $this->setMinDamage(1);
        $this->setMaxDamage(1);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "Silverfish";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
    	return [];
    }

}
