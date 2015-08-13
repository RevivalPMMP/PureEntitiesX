<?php

namespace plugin\Entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
class Spider extends Monster{
    const NETWORK_ID = 35;

    public $width = 1.5;
    public $height = 1.2;

    protected $speed = 1.13;

    public function initEntity(){
        $this->setMaxHealth(16);
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        $this->setDamage([0, 2, 2, 3]);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "거미";
    }

    public function attackEntity(Entity $player){
        if($this->distance($player) <= 1.1){
            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops(){
        return $this->lastDamageCause instanceof EntityDamageByEntityEvent ? [Item::get(Item::STRING, 0, mt_rand(0, 3))] : [];
    }

}
