<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class CaveSpider extends Monster{
    const NETWORK_ID = 40;

    public $width = 1.5;
    public $height = 1.2;

    protected $speed = 1.3;

    public function initEntity(){
        $this->setMaxHealth(12);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        $this->setMinDamage([2, 3]);
        $this->setMaxDamage([2, 3]);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "CaveSpider";
    }

    public function attackEntity(Entity $player){
        if($this->attackDelay > 10 && $this->distanceSquared($player) < 1.32){
            $this->attackDelay = 0;
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, mt_rand(0, 2))] : [];
    }

}
