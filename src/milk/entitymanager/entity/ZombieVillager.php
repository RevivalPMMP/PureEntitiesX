<?php

namespace milk\entitymanager\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class ZombieVillager extends Monster{
    const NETWORK_ID = 44;

    public $width = 0.72;
    public $height = 1.8;

    protected $speed = 1.1;

    public function initEntity(){
        if(isset($this->namedtag->Health)){
            $this->setHealth((int) $this->namedtag["Health"]);
        }else{
            $this->setHealth($this->getMaxHealth());
        }
        $this->setMinDamage([0, 3, 4, 6]);
        $this->setMaxDamage([0, 3, 4, 6]);
        parent::initEntity();
        $this->created = true;
    }

    public function getName(){
        return "ZombieVillager";
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
            switch(mt_rand(0, 2)){
                case 0:
                    $drops[] = Item::get(Item::FEATHER, 0, 1);
                    break;
                case 1:
                    $drops[] = Item::get(Item::CARROT, 0, 1);
                    break;
                case 2:
                    $drops[] = Item::get(Item::POTATO, 0, 1);
                    break;
            }
        }
        return $drops;
    }

}
