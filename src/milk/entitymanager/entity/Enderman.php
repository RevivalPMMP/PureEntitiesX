<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class Enderman extends Monster{
    const NETWORK_ID = 38;

    public $width = 0.7;
    public $height = 2.8;

    protected $speed = 1.21;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        $this->setMinDamage([0, 1, 2, 3]);
        $this->setMaxDamage([0, 1, 2, 3]);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "Enderman";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        $drops = [];
        if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
            $drops[] = Item::get(Item::END_STONE, 0, 1);
        }
        return [];
    }

}
